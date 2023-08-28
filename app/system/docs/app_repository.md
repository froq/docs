# Repository [THE_SOURCE_CODE](//github.com/froq/froq/blob/master/src/app/Repository.php)

All repositories must extend `froq\app\Repository` class that comes some handy *final* method and *protected* properties.

These properties are `$db` (instance of `froq\database\Database`) for query and CRUD operations and `$em` (instance of `froq\database\entity\EntityManager`) for entity operations.

Additionally, all repositories provide a proxy interface for some query and CRUD methods of `froq\database\entity\EntityManager` class via `froq\database\trait\RepositoryTrait::__call()` method ([source](//github.com/froq/froq-database/blob/master/src/trait/RepositoryTrait.php)), they make possible to use entities for these purposes.

Here we'll see some basic examples of using repositories, but not go to details since [database](/docs/database) and [entities](/docs/database-entities) are explained there.

```php
// File: app/repository/BookRepository.php
namespace app\repository;

use froq\app\Repository;
use froq\database\query\QueryParams;

class BookRepository extends Repository {
    public function find(int $id): ?array {
        return $this->initQuery()
                    ->select('*')
                    ->from('books')
                    ->where('id', [$id])
                    ->get();
    }

    public function findAll(QueryParams $qp, ?int $page = null, int $limit = 10): ?array {
        return $this->initQuery()
                    ->select('*')
                    ->from('books')
                    ->where($qp)
                    ->order('id', -1)
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

    public function showAction(int $id) {
        /** @var array|null */
        $book = $this->repository->find($id);
        $status = $book ? Status::OK : Status::NOT_FOUND;

        return $this->view('show', data: ['book' => $book], status: $status);
    }

    public function searchAction() {
        $qp = $this->repository->initQueryParams();

        /** @var UrlQuery|null (sugar) */
        if ($q = $this->request->query()) {
            $page = (int) $q->get('page');

            $q->has('isbn') && $qp->addIn('isbn', $q->get('isbn'));
            $q->has('price') && $qp->addBetween('price', $q->get('price'));
            // ...
        }

        /** @var array<array>|null */
        $books = $this->repository->findAll($qp, $page ?? null);
        $status = $books ? Status::OK : Status::NOT_FOUND;

        return $this->view('search', data: ['books' => $books], status: $status);
    }
}
```

### Using entities
```php
// File: app/entity/Book.php
namespace app\entity;

#[meta(table: "books", primary: "id")]
class Book extends \froq\database\entity\Entity {
    #[meta(field: "id")]
    var $id;

    #[meta(field: "name")]
    var $name;
}
```

```php
// File: app/entity/BookList.php
namespace app\entity;

class BookList extends \froq\database\entity\EntityList {}
```

```php
// File: app/controller/BookController.php
namespace app\controller;

use app\entity\{Book, BookList};
use froq\http\response\Status;

class BookController extends \froq\app\Controller {
    public bool $useRepository = true;

    public function showAction(int $id) {
        /** @var Book */
        $book = $this->repository->find(new Book(id: $id));
        $status = $book->isFound() ? Status::OK : Status::NOT_FOUND;

        return $this->view('show', data: ['book' => $book], status: $status);
    }

    public function searchAction() {
        $qb = $this->repository->initQuery();

        /** @var UrlQuery|null (sugar) */
        if ($q = $this->request->query()) {
            $page = (int) $q->get('page');

            $q->has('isbn') && $qb->in('isbn', $q->get('isbn'));
            $q->has('price') && $qb->between('price', $q->get('price'));
            // ...

            $page && $qb->paginate($page);
        }

        /** @var BookList<Book> */
        $books = $this->repository->findBy(new Book(), $qb);
        $status = $books->isNotEmpty() ? Status::OK : Status::NOT_FOUND;

        return $this->view('search', data: ['books' => $books], status: $status);
    }
}
```

<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
