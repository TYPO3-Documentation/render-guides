<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Controller;

use MyVendor\MyExtension\Domain\Model\Blog;
use MyVendor\MyExtension\Domain\Repository\BlogRepository;
use MyVendor\MyExtension\Domain\Repository\PostRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;

final class BlogController extends ActionController
{
    private const ITEMS_PER_PAGE = 10;

    public function __construct(
        private readonly BlogRepository $blogRepository,
        private readonly PostRepository $postRepository,
    ) {}

    public function listAction(int $currentPage = 1): ResponseInterface
    {
        $blogs = $this->blogRepository->findAll();
        $paginator = new QueryResultPaginator($blogs, $currentPage, self::ITEMS_PER_PAGE);

        $this->view->assignMultiple([
            'blogs' => $paginator->getPaginatedItems(),
            'pagination' => new SimplePagination($paginator),
        ]);

        return $this->htmlResponse();
    }

    public function showAction(Blog $blog, int $currentPage = 1): ResponseInterface
    {
        $posts = $this->postRepository->findByBlog($blog);
        $paginator = new QueryResultPaginator($posts, $currentPage, self::ITEMS_PER_PAGE);

        $this->view->assignMultiple([
            'blog' => $blog,
            'posts' => $paginator->getPaginatedItems(),
            'pagination' => new SimplePagination($paginator),
        ]);

        return $this->htmlResponse();
    }

    public function deleteAction(Blog $blog): ResponseInterface
    {
        $this->blogRepository->remove($blog);
        $this->addFlashMessage('The blog was deleted.');

        return $this->redirect('list');
    }
}
