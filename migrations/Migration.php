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

class Migration extends \Xpressengine\Support\Migration
{
    private $table = 'comment_target';

    /**
     * 서비스에 필요한 자체 환경(타 서비스와 연관이 없는 환경)을 구축한다.
     * 서비스의 db table 생성과 같은 migration 코드를 작성한다.
     *
     * @return mixed
     */
    public function install()
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

            $table->foreign('doc_id')->references('id')->on('documents');
            $table->foreign('target_author_id')->references('id')->on('user');
        });
    }

    /**
     * drop table when plugin uninstall
     * @return void
     */
    public function uninstall()
    {
        Schema::dropIfExists($this->table);
    }

    /**
     * @return bool
     */
    public function tableExists()
    {
        return Schema::hasTable($this->table);
    }
}
