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

namespace Dvelum\App\Frontend\Articles\Item;

use Dvelum\App\Frontend;
use Dvelum\Config;
use Dvelum\Config\ConfigInterface;
use Dvelum\Request;
use Dvelum\Response;
use Dvelum\Lang;
use Dvelum\Orm\Model;
use Dvelum\App\Session\User;
use Dvelum\View;

class Controller extends Frontend\Controller
{
    /**
     * Articles config
     * @var ConfigInterface $config
     */
    protected $config;


    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);

        $this->config = Config::storage()->get('articles.php');
        $externalsConfig = $this->appConfig->get('externals');
        $this->resource->addCss($externalsConfig['resources_root'] . 'dvelum-module-articles/css/style.css', 100);

        // add localization dictionary
        Lang::addDictionaryLoader(
            'dvelum_articles',
            $this->appConfig->get('language') . '/dvelum_articles.php',
            Config\Factory::File_Array
        );
    }

    public function indexAction()
    {
        $code = $this->request->getPart(1);
        $vers = $this->request->get('vers', 'integer', false);

        if (empty($code)) {
            $this->response->redirect('/');
            return;
        }

        /**
         * @var \Model_Dvelum_Article $articlesModel
         */
        $articlesModel = Model::factory('Dvelum_Article');
        $categoriesModel = Model::factory('Dvelum_Article_Category');

        if ($vers) {
            $data = $articlesModel->getItemByField('url', $code);
        } else {
            $data = $articlesModel->getCachedItemByField('url', $code);
        }

        if ($vers && User::getInstance()->isAuthorized()) {
            $data = Model::factory('Vc')->getData('Dvelum_Article', $data['id'], $vers);
        } else {
            if (!empty($data) && !$data['published']) {
                $data = [];
            }
        }

        /*
         * Check if article is published
         */
        if (empty($data)) {
            $this->response->notFound();
            $this->response->send();
            return;
        }

        $scheme = 'http://';
        if ($this->request->isHttps()) {
            $scheme = 'https://';
        }

        $pageUrl = $scheme . $this->request->server('HTTP_HOST', 'string', '') . $this->request->url([
                $this->router->findUrl('dvelum_articles_item'),
                $data['url']
            ]);

        if (empty($data['date_published'])) {
            $data['date_published'] = date('Y-m-d H:i:s');
        }

        $page = \Page::getInstance();
        $page->date_published = $data['date_published'];
        $page->html_title = $data['title'];
        $page->page_title = $data['title'];
        $page->meta_keywords = $data['meta_keywords'];
        $page->meta_description = $data['meta_description'];

        $page->setOgProperty('title', $data['title']);
        $page->setOgProperty('url', $pageUrl);
        $page->setOgProperty('description', $data['brief']);
        $page->setOgProperty('type', 'article');

        // Get article main category
        $categoryInfo = $categoriesModel->getCachedItem($data['main_category']);
        if (!empty($categoryInfo)) {
            $categoryInfo['url'] = $this->request->url([
                $this->router->findUrl('dvelum_articles'),
                $categoryInfo['url']
            ]);
        }

        // Get article image url
        if (!empty($data['image'])) {
            $imgData = Model::factory('Medialib')->getCachedItem($data['image']);
            if (!empty($imgData)) {
                $data['image'] = \Model_Medialib::getImgPath($imgData['path'], $imgData['ext'], $this->config->get('article_image_size'));
                //Open Graph property
                $page->setOgProperty('image',
                    $scheme . $this->request->server('HTTP_HOST', 'string', '') . $data['image']);
            } else {
                $data['image'] = '';
            }
        }

        // Article Item page code
        $itemUrl = $this->router->findUrl('dvelum_articles_item');

        // Get related articles
        $relatedCount = $this->config->get('related_count');
        $relatedArticles = [];
        if ($relatedCount) {
            $relatedArticles = $articlesModel->getRelated($data['id'], $data['main_category'], $data['date_published'],
                $this->config->get('related_count'));
            if (!empty($relatedArticles)) {
                // apply image urls
                $relatedArticles = $articlesModel->addImagePaths($relatedArticles,
                    $this->config->get('related_image_size'), 'image');
                // apply articles urls
                foreach ($relatedArticles as &$item) {
                    $item['url'] = $this->request->url([$itemUrl, $item['url']]);
                }
                unset($item);
            }
        }

        // Prepare template
        $template = View::factory();
        $template->setData([
            'page' => $page,
            'data' => $data,
            'related_articles' => $relatedArticles,
            'itemUrl' => $itemUrl,
            'lang' => Lang::lang('dvelum_articles'),
            'date_format' => $this->config->get('date_format'),
            'category_info' => $categoryInfo
        ]);

        /*
         * Render article content
         */
        $page->text .= $template->render('dvelum_articles/article.php');

        if (isset($data['allow_comments']) && $data['allow_comments'] && $this->config['comments_tpl']) {
            $cTemplate = View::factory([
                'itemUrl' => $itemUrl,
                'itemId' => $data['id']
            ]);
            $page->text .= $cTemplate->render($this->config['comments_tpl']);
        }
    }
}