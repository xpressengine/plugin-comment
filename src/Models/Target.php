<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Models;

use Xpressengine\Database\Eloquent\DynamicModel;
use Xpressengine\User\Models\UnknownUser;
use Xpressengine\User\Models\User;
use Xpressengine\User\UserInterface;

/**
 * Class Target
 *
 * @property Comment $comment
 * @property User|null $author
 *
 * @package Xpressengine\Plugins\Comment\Models
 */
class Target extends DynamicModel
{
    protected $table = 'comment_target';

    protected $connection = 'document';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['docId', 'targetId', 'targetAuthorId'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'docId');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'targetAuthorId');
    }

    /**
     * Returns the author
     *
     * @return UserInterface
     */
    public function getAuthor()
    {
        if (!$author = $this->getRelationValue('author')) {
            $author = new UnknownUser();
        }

        return $author;
    }
}
