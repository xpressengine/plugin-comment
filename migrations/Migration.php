<?php

/**
 * Migration.php
 *
 * PHP version 7
 *
 * @category
 * @package
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
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
        Schema::create($this->table, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->increments('id');
            $table->string('doc_id', 36);
            $table->string('target_id', 36);
            $table->string('target_author_id', 36)->nullable();
            $table->string('target_type')->nullable();

            $table->unique('doc_id');
            $table->index('target_id');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }

    public function tableExists()
    {
        return Schema::hasTable($this->table);
    }
}
