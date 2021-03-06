<?php

namespace BD\Bundle\WordpressAPIBundle\Controller;

use BD\Bundle\WordpressAPIBundle\Service\CategoryServiceInterface;
use BD\Bundle\WordpressAPIBundle\Service\PostServiceInterface;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use BD\Bundle\XmlRpcBundle\XmlRpc\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController
{
    /** @var Repository */
    protected $repository;

    /** @var SearchService */
    protected $searchService;

    /** @var ContentService */
    protected $contentService;

    /** @var LocationService */
    protected $locationService;

    /** @var ContentTypeService */
    protected $contentTypeService;

    /** @var UserService */
    protected $userService;

    /** @var CategoryServiceInterface */
    protected $categoryService;

    /** @var PostServiceInterface */
    protected $postService;

    public function __construct( Repository $repository, CategoryServiceInterface $categoryService, PostServiceInterface $postService )
    {
        $this->repository = $repository;
        $this->categoryService = $categoryService;
        $this->postService = $postService;

        $this->searchService = $repository->getSearchService();
        $this->contentService = $repository->getContentService();
        $this->locationService = $repository->getLocationService();
        $this->contentTypeService = $repository->getContentTypeService();
        $this->userService = $repository->getUserService();
    }

    public function getCategoryList()
    {
        return new Response( $this->categoryService->getList() );
    }

    public function setPostCategories( $postId, Request $request )
    {
        $this->login( $request->request->get( 'username' ), $request->request->get( 'password' ) );

        // @todo Replace categories instead of adding
        $contentInfo = $this->contentService->loadContentInfo( $postId );
        foreach ( $request->request->get( 'categories' ) as $category )
        {
            $this->locationService->createLocation(
                $contentInfo,
                $this->locationService->newLocationCreateStruct( $category['categoryId'] )
            );
        }
        return new Response( true );
    }

    public function getPostCategories( $postId )
    {
        return new Response( $this->categoryService->getPostCategories( $postId ) );
    }

    public function getSupportedMethods()
    {
        return new Response(
            array(
                'blogger.getUsersBlogs',
                'mt.getRecentPostTitles',
                'mt.getCategoryList',
                'mt.setPostCategories',
                'mt.getPostCategories',
                'mt.supportedMethods',
                'metaWeblog.getCategories',
                'metaWeblog.getRecentPosts',
                'metaWeblog.newPost',
                'metaWeblog.editpost',
                'metaWeblog.deletePost',
                'metaWeblog.getPost',
                'metaWeblog.getCategories',
                'system.listMethods',
                'wp.getUsersBlogs',
                'wp.getOptions',
                'wp.getProfile',
                'wp.getMediaLibrary',
                'wp.getMediaItem',
                'wp.uploadFile'
            )
        );
    }

    public function getProfile( Request $request )
    {
        $user = $this->userService->loadUserByCredentials(
            $request->request->get( 'username' ),
            $request->request->get( 'password' )
        );

        return new Response(
            array(
                'userid' => $user->contentInfo->id,
                'nickname' => $user->contentInfo->name,
                'firstname' => (string)$user->getFieldValue( 'first_name' ),
                'lastname' => (string)$user->getFieldValue( 'last_name' ),
                'url' => '',
            )
        );
    }

    public function getComments()
    {
        return new Response( array() );
    }

    public function getPostFormats()
    {
        return new Response(
            array(
                'standard' => 'Standard'
            )
        );
    }

    private function login( $username, $password )
    {
        $this->repository->setCurrentUser(
            $this->userService->loadUserByCredentials( $username, $password )
        );
    }
}
