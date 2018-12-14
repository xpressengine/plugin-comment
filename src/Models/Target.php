<?php
/**
 * Target.php
 *
 * PHP version 5
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Models;

use Xpressengine\Database\Eloquent\DynamicModel;
use Xpressengine\User\Models\UnknownUser;
use Xpressengine\User\Models\User;
use Xpressengine\User\UserInterface;

/**
 * Target
 *
 * @property Comment $comment
 * @property User|null $author
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */
class Target extends DynamicModel
{
    protected $table = 'comment_target';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['doc_id', 'target_id', 'target_author_id', 'target_type'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'doc_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @deprecated since 0.9.18
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'target_author_id');
    }

    /**
     * Returns the author
     *
     * @return UserInterface
     * @deprecated since 0.9.18
     */
    public function getAuthor()
    {
        if (!$author = $this->getRelationValue('author')) {
            $author = new UnknownUser();
        }

        return $author;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function commentable()
    {
        return $this->morphTo('target');
    }
}
