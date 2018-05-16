<?php
/**
 *  DVelum project https://github.com/dvelum/dvelum
 *  Copyright (C) 2011-2017  Kirill Yegorov
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Dvelum\App\Block;

use Dvelum\Config;
use Dvelum\Lang;
use Dvelum\View;
use Dvelum\Resource;
use Dvelum\Orm\Model;

/**
 * Top articles
 */
class Articles extends AbstractAdapter
{
    protected $template = 'dvelum_articles/block_articles.php';

    const cacheable = false;
    const dependsOnPage = false;
    const CACHE_KEY = 'block_dvelum_articles_';

    static public function getCacheKey($id){
        return md5(self::CACHE_KEY . '_' . $id);
    }

    /**
     * (non-PHPdoc)
     * @see AbstractAdapter::render()
     */
    public function render() : string
    {
        $articlesConfig = Config::storage()->get('articles.php');
        $appConfig = Config::storage()->get('main.php');
        $externalsConfig = $appConfig->get('externals');
        Resource::factory()->addCss($externalsConfig['resources_root'] . 'dvelum-module-articles/css/style.css', 100);

        // Add localization dictionary loader
        Lang::addDictionaryLoader('dvelum_articles', $appConfig->get('language').'/dvelum_articles.php');
        $data = Model::factory('Dvelum_Article')->getTop(false , $articlesConfig->get('block_count'), $articlesConfig->get('block_image_size'));

        $tpl = View::factory();
        $tpl->setData([
            'config' => $this->config,
            'place' => $this->config['place'],
            'data' => $data,
            'date_format' => $articlesConfig->get('date_format'),
            'lang' => Lang::lang('dvelum_articles')
        ]);

        return $tpl->render($this->template);
    }
}