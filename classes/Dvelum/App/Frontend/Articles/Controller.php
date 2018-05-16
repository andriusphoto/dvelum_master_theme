<?php
/**
 * DVelum project http://code.google.com/p/dvelum/ , https://github.com/k-samuel/dvelum , http://dvelum.net
 * Copyright (C) 2011-2017  Kirill Yegorov
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace  Dvelum\App\Frontend\Articles;

use Dvelum\Filter;
use Dvelum\App\Frontend;
use Dvelum\Config;
use Dvelum\Config\ConfigInterface;
use Dvelum\Request;
use Dvelum\Response;
use Dvelum\Orm\Model;
use Dvelum\Lang;
use Dvelum\View;

class Controller extends Frontend\Controller
{
    /**
     * @avr \Model_Dvelum_Article_Category $categoryModel
     */
    protected $categoryModel;
    /**
     * @var \Model_Dvelum_Article $articleModel
     */
    protected $articleModel;
    /**
     * Article categories ifo
     * @var array
     */
    protected $categories;
    /**
     * Articles config
     * @var ConfigInterface $config
     */
    protected $config;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);

        $externalsConfig = $this->appConfig->get('externals');

        $this->resource->addCss($externalsConfig['resources_root'] . 'dvelum-module-articles/css/style.css', 100);

        $this->categoryModel = Model::factory('Dvelum_Article_Category');
        $this->articleModel = Model::factory('Dvelum_Article');
        $this->categories = $this->categoryModel->getPublished();
        $this->config = Config::storage()->get('articles.php');

        // add localization dictionary
        Lang::addDictionaryLoader('dvelum_articles', $this->appConfig->get('language').'/dvelum_articles.php', Config\Factory::File_Array);
    }

    public function indexAction()
    {
        $category = $this->request->getPart(1);

        if(isset($this->categories[$category])){
            $this->listCategory($category);
        }else{
            $this->listTop();
        }
    }

    /**
     * Show category page list
     * @param $category
     */
    protected function listCategory($category)
    {
        $page =  intval($this->request->getPart(2));

        $category = $this->categories[$category];

        if(!$page || $page<0)
            $page = 1;

        $count = $this->articleModel->getPublishedCount($category['id']);
        $articles = [];
        if($count)
        {
            $articles = $this->articleModel->getTop(
                $category['id'],
                $this->config->get('list_count') ,
                ($this->config->get('list_count') * ($page-1)),
                $this->config->get('preview_image_size')
            );

            $articleItemUrl = $this->router->findUrl('dvelum_articles_item');

            if(!empty($articles)){
                foreach($articles as &$item){
                    $item['url'] = $this->request->url([$articleItemUrl , $item['url']]);
                }unset($item);
            }
        }

        $categoryUrl = $this->router->findUrl('dvelum_articles');

        $categories = [];
        foreach ($this->categories as $code => $item) {
            $item['url'] = $this->request->url([$categoryUrl , $item['url']]);
            $categories[$code] = $item;
        }

        $pager = new \Paginator();
        $pager->curPage = $page;
        $pager->numLinks = 5;
        $pager->pageLinkTpl = $this->request->url([$categoryUrl ,$category['url'] , '[page]']);
        $pager->numPages = ceil($count / $this->config->get('list_count'));

        $page = \Page::getInstance();
        $template = View::factory();
        $template->setData([
            'articles' => $articles,
            'page' => $page,
            'pager' => $pager,
            'category' => $category,
            'cat_list' => $categories,
            'date_format' => $this->config->get('date_format'),
        ]);


        $scheme = 'http://';
        if($this->request->isHttps()){
            $scheme = 'https://';
        }

        $page->page_title = $category['title'];
        $page->html_title = $category['title'];
        $page->meta_keywords = $category['meta_keywords'];
        $page->meta_description = $category['meta_description'];

        $page->setOgProperty('title', $category['title']);
        $page->setOgProperty('url', $scheme . $this->request->server('HTTP_HOST', Filter::FILTER_STRING, '').$this->request->url([$categoryUrl, $category['url']]));
        $page->setOgProperty('description', $category['meta_description']);

        $page->text = $template->render('dvelum_articles/category.php');
    }

    /**
     * show main page list
     * @throws \Exception
     */
    protected function listTop()
    {
        $page =  intval($this->request->getPart(1));

        if(!$page || $page<0)
            $page = 1;

        $categoryUrl = $this->router->findUrl('dvelum_articles');

        $categories = [];

        foreach ($this->categories as $code => $item) {
            $item['url'] = $this->request->url([$categoryUrl , $item['url']]);
            $categories[$code] = $item;
        }

        $count = $this->articleModel->getPublishedCount();

        $pager = new \Paginator();
        $pager->curPage = $page;
        $pager->numLinks = 5;
        $pager->pageLinkTpl = $this->request->url([$categoryUrl ,'[page]']);
        $pager->numPages = ceil($count / $this->config->get('list_count'));

        $articles = $this->articleModel->getTop(
            false,
            $this->config->get('list_count') , (
            $this->config->get('list_count')*($page-1)),
            $this->config->get('preview_image_size')
        );

        $articleItemUrl = $this->router->findUrl('dvelum_articles_item');

        if(!empty($articles)){
            foreach($articles as &$item){
                $item['url'] = $this->request->url([$articleItemUrl , $item['url']]);
            }unset($item);
        }

        $template = View::factory();
        $template->setData(array(
            'cat_list' => $categories,
            'articles' => $articles,
            'pager' => $pager,
            'itemUrl' => $this->router->findUrl('dvelum_articles_item'),
            'lang' =>Lang::lang('dvelum_articles'),
            'date_format' => $this->config->get('date_format')
        ));

        $page = \Page::getInstance();
        $page->text.= $template->render('dvelum_articles/main.php');
    }
}