<?php

/**
 * Migration.php
 *
 * PHP version 5
 *
 * @category
 * @package
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Schema;
use XeDB;

class Migration
{
    private $table = 'comment_target';

    /**
     * @return void
     */
    public function up()
    {
        $this->schema()->create($this->table, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->increments('id');
            $table->string('doc_id', 36);
            $table->string('target_id', 36);
            $table->string('target_author_id', 36);

            $table->unique('doc_id');
            $table->index('target_id');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->schema()->dropIfExists($this->table);
    }

    /**
     * @return \Illuminate\Database\Schema\Builder
     */
    private function schema()
    {
        return Schema::setConnection(XeDB::connection('document')->master());
    }

    public function tableExists()
    {
        return $this->schema()->hasTable($this->table);
    }
}
