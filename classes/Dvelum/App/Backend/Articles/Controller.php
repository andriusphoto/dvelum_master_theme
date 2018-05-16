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
namespace Dvelum\App\Backend\Articles;
/**
 *  Articles UI controller
 */
use Dvelum\Config;
use Dvelum\App\Router\RouterInterface;
use Dvelum\Orm;
use Dvelum\Orm\Record as OrmRecord;
use Dvelum\Orm\RecordInterface;
use Dvelum\Orm\Model;
use Dvelum\App\Backend\Api;
use Dvelum\App\Controller\EventManager;
use Dvelum\App\Controller\Event;
use Dvelum\Filter;

class Controller extends Api\Controller
{
    protected  $listFields = [
        "id",
        "title",
        "url",
        "allow_comments",
        "meta_keywords",
        "image",
        "main_category",
        "date_created",
        "date_published",
        "date_updated",
        "author_id",
        "editor_id",
        "published",
        "brief",
        "published_version",
        "last_version"
    ];

    protected $canViewObjects = ['dvelum_article_category'];
    protected $listLinks = ['category_title'=>'main_category','user'=>'editor_id'];

    public function getModule(): string
    {
        return 'Dvelum_Articles';
    }

    public function getObjectName(): string
    {
        return 'Dvelum_Article';
    }

    public function initListeners()
    {
        $this->eventManager->on(EventManager::AFTER_LIST, [$this, 'prepareList']);
    }

    /**
     * Find staging URL
     * @param Orm\RecordInterface $object
     * @return string
     */
    public function getStagingUrl(Orm\RecordInterface $object) : string
    {
        $frontConfig = Config::storage()->get('frontend.php');
        $routerClass = '\\Dvelum\\App\\Router\\' . $frontConfig->get('router');

        if(!class_exists($routerClass)) {
            $routerClass = $frontConfig->get('router');
        }
        /**
         * @var RouterInterface $router
         */
        $router = new $routerClass();
        $stagingUrl = $router->findUrl(strtolower('dvelum_articles_item'));

        if(!strlen($stagingUrl))
            return $this->request->url(['']);

        return $this->request->url([$stagingUrl , $object->get('url')]);
    }

    /**
     * Prepare articles list
     * @param Event $event
     */
    public function prepareList(Event $event)
    {
        $data = &$event->getData()->data;

        /**
         * @var \Model_Dvelum_Article $articleModel
         */
        $articleModel = Model::factory('Dvelum_Article');

        if(empty($data))
            return;

        $data = $articleModel->addImagePaths($data, 'medium', 'image');

        $frontConfig = Config::storage()->get('frontend.php');
        $routerClass = '\\Dvelum\\App\\Router\\' . $frontConfig->get('router');

        if(!class_exists($routerClass)) {
            $routerClass = $frontConfig->get('router');
        }
        /**
         * @var RouterInterface $router
         */
        $router = new $routerClass();

        $stagingUrl = $router->findUrl('dvelum_articles_item');

        foreach($data as $k=>&$v) {
            $v['staging_url'] = $this->request->url([$stagingUrl, $v['url']]);
        }unset($v);
    }

    /**
     * Save article on drop event
     */
    public function dropAction()
    {
        if(!$this->checkCanEdit() || !$this->checkCanPublish()){
            return;
        }

        $id = $this->request->post('id', Filter::FILTER_INTEGER, false);
        $published = $this->request->post('published', Filter::FILTER_BOOLEAN, false);

        try{
            /**
             * @var RecordInterface $article
             */
            $article = OrmRecord::factory('dvelum_article', $id);
        }catch (\Exception $e){
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if(!$this->checkOwner($article)){
            return;
        }

        $acl = $article->getConfig()->getAcl();
        if($acl && !$acl->canPublish($article)){
            $this->response->error($this->lang->get('CANT_PUBLISH'));
            return;
        }

        if($published)
        {
            /**
             * @var \Model_Vc $versionControlModel
             */
            $versionControlModel = Model::factory('Vc');
            $version = $versionControlModel->getLastVersion($this->getObjectName() , $id);

            if(!$article->publish($version)){
                $this->response->error($this->lang->get('CANT_EXEC'));
                return;
            }

            $this->response->success(['published_version'=>$version]);
        }else{
            if(!$article->unpublish()){
                $this->response->error($this->lang->get('CANT_EXEC'));
                return;
            }
            $this->response->success(['published_version'=>0]);
        }
    }

    /**
     * Get list of categories for ComboBox filter
     */
    public function categoriesAction()
    {
        $model = Model::factory('dvelum_article_category');
        $list = $model->query()->params(['sort'=>'title','dir'=>'ASC'])->fields(['id','title'])->fetchAll();
        $this->response->success($list);
    }
}