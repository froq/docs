# Repository [#git](//github.com/froq/froq/blob/master/src/app/Repository.php)

All repositories must extend `froq\app\Repository` class that comes some handy *final* methods and *protected* properties.

These properties are `$db` (instance of `froq\database\Database`) for CRUD & query operations and `$em` (instance of `froq\database\entity\EntityManager`) for entity operations.

Additionally, all repositories provide a proxy interface for some CRUD & query methods of `froq\database\entity\EntityManager` class via `froq\database\trait\RepositoryTrait::__call()` method ([source](//github.com/froq/froq-database/blob/master/src/trait/RepositoryTrait.php)), and they make possible to use entities for these purposes.

Here we'll see some basic examples of using repositories, but not go into details since [database](/docs/database) and [entities](/docs/database-entities) are explained there.

```php
// File: app/repository/BookRepository.php
namespace app\repository;

use froq\app\Repository;
use froq\database\query\QueryParams;

class BookRepository extends Repository {
    /** @return array|null */
    public function find(int $id): ?array {
        /** @var froq\database\Query */
        return $this->initQuery('books')
            ->select('*')
            ->where('id', [$id])
            ->get();
    }

    /** @return array<array>|null */
    public function findAll(QueryParams $qp, int $page, int $limit = 10): ?array {
        /** @var froq\database\Query */
        return $this->initQuery('books')
            ->select('*')
            ->where($qp)
            ->order('id', 'DESC')
            ->paginate($page, $limit)
            ->getAll();
    }
}
```

```php
// File: app/controller/BookController.php
namespace app\controller;

use froq\app\Controller;
use froq\http\response\Status;

class BookController extends Controller {
    public bool $useRepository = true;
    public bool $useView = true;

    public function showAction(int $id) {
        /** @var array|null */
        $book = $this->repository->find($id);
        $status = $book ? Status::OK : Status::NOT_FOUND;

        return $this->view('show', ['book' => $book], $status);
    }

    public function searchAction() {
        /** @var froq\database\query\QueryParams */
        $qp = $this->repository->initQueryParams();

        /** @var UrlQuery|null (sugar) */
        if ($q = $this->request->query()) {
            $page = (int) $q->get('page');

            $q->has('isbn') && $qp->addIn('isbn', $q->get('isbn'));
            $q->has('price') && $qp->addBetween('price', $q->get('price'));
            // ...
        }

        /** @var array<array>|null */
        $books = $this->repository->findAll($qp, $page ?? 1);
        $status = $books ? Status::OK : Status::NOT_FOUND;

        return $this->view('search', ['books' => $books], $status);
    }
}
```

### Using entities
Since all repositories come with some CRUD & query methods, it's possible to use them directly with entities without creating any (specifically declared) methods in your repositories.

```php
// File: app/entity/Book.php
namespace app\entity;

#[meta(table: 'books', primary: 'id')]
class Book extends \froq\database\entity\Entity {
    #[meta(field: 'id')]
    public $id;

    #[meta(field: 'name')]
    public $name;

    #[meta(field: 'isbn')]
    public $isbn;

    #[meta(field: 'price')]
    public $price;

    // For save() calls only.
    public function validations(): array {
        return [
            'id'    => ['type' => 'int'],
            'name'  => ['type' => 'string', 'required'],
            'isbn'  => ['type' => 'string', 'required']
            'price' => ['type' => 'float',  'required'],
        ];
    }
}
```

```php
// File: app/entity/BookList.php
namespace app\entity;

class BookList extends \froq\database\entity\EntityList {
    // Inherits count, check, iteration & util methods.
}
```

```php
// File: app/controller/BookController.php
namespace app\controller;

use app\entity\{Book, BookList};
use froq\http\response\Status;
use froq\validation\ValidationError;
use Throwable;

class BookController extends \froq\app\Controller {
    public bool $useRepository = true;

    /** @call POST /book */
    public function addAction() {
        $book = $error = null;

        try {
            /** @var Book */
            $book = $this->repository->save(new Book(
                // "..." will call Entity.fill() method to set properties.
                ...$this->request->post(['name', 'isbn', 'price'], combine: true)
            ));
            // If no such book saved, isSaved() = false.
            $status = $book->isSaved() ? Status::CREATED : Status::INTERNAL;
        } catch (ValidationError $e) {
            $status = Status::BAD_REQUEST;
            $error['details'] = $e->errors();
        } catch (Throwable) {
            $status = Status::INTERNAL_SERVER_ERROR;
            $error['details'] = 'Internal server error';
        }

        $this->response->json($status, ['data' => ['book' => $book], 'error' => $error]);
    }

    /** @call PUT /book/:id */
    public function updateAction(int $id) {
        $book = $error = null;

        try {
            /** @var Book */
            $book = $this->repository->save(new Book(
                // Assign target id.
                id: $id,
                // "..." will call Entity.fill() method to set properties.
                ...$this->request->post(['name', 'isbn', 'price'], combine: true)
            ));
            // If no such book exists, isSaved() = false.
            $status = $book->isSaved() ? Status::ACCEPTED : Status::NOT_FOUND;
        } catch (ValidationError $e) {
            $status = Status::BAD_REQUEST;
            $error['details'] = $e->errors();
        } catch (Throwable) {
            $status = Status::INTERNAL_SERVER_ERROR;
            $error['details'] = 'Internal server error';
        }

        $this->response->json($status, ['data' => ['book' => $book], 'error' => $error]);
    }

    /** @call DELETE /book/:id */
    public function deleteAction(int $id) {
        /** @var Book */
        $book = $this->repository->remove(new Book(id: $id));
        $status = $book->isRemoved() ? Status::OK : Status::NOT_FOUND;

        $this->response->json($status, ['data' => ['book' => $book]]);
    }

    /** @call GET /book/:id */
    public function showAction(int $id) {
        /** @var Book */
        $book = $this->repository->find(new Book(id: $id));
        $status = $book->isFound() ? Status::OK : Status::NOT_FOUND;

        $this->response->json($status, ['data' => ['book' => $book]]);
    }

    /** @call GET /book */
    public function listAction() {
        /** @var BookList<Book> */
        $books = $this->repository->findBy(Book::class, ...$this->getLimitOffset());
        $status = $books->isNotEmpty() ? Status::OK : Status::NOT_FOUND;

        $this->response->json($status, ['data' => ['books' => $books->toArray()]]);
    }

    /** @call GET /book/search */
    public function searchAction() {
        /** @var froq\database\Query */
        $qb = $this->repository->initQuery();

        /** @var UrlQuery|null (sugar) */
        if ($q = $this->request->query()) {
            $q->has('isbn') && $qb->in('isbn', $q->get('isbn'));
            $q->has('price') && $qb->between('price', $q->get('price'));
            ...
        }

        /** @var BookList<Book> */
        $books = $this->repository->findBy(Book::class, $qb, ...$this->getLimitOffset());
        $status = $books->isNotEmpty() ? Status::OK : Status::NOT_FOUND;

        $this->response->json($status, ['data' => ['books' => $books->toArray()]]);
    }

    private function getLimitOffset() {
        $limit = (int) $this->getParam('limit', 10);
        $offset = $limit * ((int) $this->getParam('page'));

        // Maybe..
        // if ($limit > 100) $limit = 100;

        // As named arguments (limit: .., offset: ..).
        return ['limit' => $limit, 'offset' => $offset];
    }
}
```

<br class="sep">

*Note: Looking at the (verbose) examples on this page, in case to keep controllers / actions concise, services can be used for this purpose. For example, `searchAction()` can be written like;*

```php
use app\service\BookService;
...

class BookController extends \froq\app\Controller {
    ...

    /** @call GET /book/search */
    public function searchAction() {
        $service = new BookService($this);
        [$status, $books] = $service->search();

        $this->response->json($status, ['data' => ['books' => $books->toArray()]]);
    }
}
```

```php
// File: app/service/BookService.php
namespace app\service;

use app\entity\{Book, BookList};
use froq\http\response\Status;
use froq\app\Controller;

class BookService {
    public function __construct(
        private Controller $controller
    ) {}

    public function searchAction(): array {
        /** @var froq\database\Query */
        $qb = $this->controller->repository->initQuery();

        /** @var UrlQuery|null (sugar) */
        if ($q = $this->controller->request->query()) {
            $q->has('isbn') && $qb->in('isbn', $q->get('isbn'));
            $q->has('price') && $qb->between('price', $q->get('price'));
            // ...
        }

        /** @var BookList<Book> */
        $books = $this->controller->repository->findBy(Book::class, $qb, ...$this->getLimitOffset());
        $status = $books->isNotEmpty() ? Status::OK : Status::NOT_FOUND;

        return [$status, $books];
    }

    private function getLimitOffset(): array {
        $limit = (int) $this->controller->getParam('limit', 10);
        $offset = $limit * ((int) $this->controller->getParam('page'));

        // Maybe..
        // if ($limit > 100) $limit = 100;

        // As named arguments (limit: .., offset: ..).
        return ['limit' => $limit, 'offset' => $offset];
    }
}
```
