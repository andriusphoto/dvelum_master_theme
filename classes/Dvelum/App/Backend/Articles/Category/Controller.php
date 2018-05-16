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
namespace Dvelum\App\Backend\Articles\Category;
/**
 *  Articles UI controller
 */
use Dvelum\App\Backend;


class Controller extends Backend\Ui\Controller
{
    protected $listFields = [
        "title",
        "url",
        "id",
        "date_created",
        "date_published",
        "date_updated",
        "author_id",
        "editor_id",
        "published",
        "published_version",
        "last_version"
    ];

    public function getModule(): string
    {
        return 'Dvelum_Articles_Category';
    }

    public function getObjectName(): string
    {
        return 'Dvelum_Article_Category';
    }
}