<?php
/**
 * Comment.php
 *
 * PHP version 7
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Models;

use Illuminate\Database\Eloquent\Collection;
use Xpressengine\Document\Models\Document;
use Xpressengine\Storage\File;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\Models\UnknownUser;
use Xpressengine\User\Models\User;
use Xpressengine\User\UserInterface;

/**
 * Comment
 *
 * @property User|null $author
 * @property Target $target
 * @property Collection $files
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class Comment extends Document
{
    protected $voteType;

    /**
     * author
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * target
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function target()
    {
        return $this->hasOne(Target::class, 'doc_id');
    }

    /**
     * fiels
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function files()
    {
        $file = new File;
        return $this->belongsToMany(File::class, $file->getFileableTable(), 'fileable_id', 'file_id');
    }

    /**
     * Returns the author
     *
     * @return UserInterface
     */
    public function getAuthor()
    {
        if (!$author = $this->getRelationValue('author')) {
            return !empty($this->user_id) ? new UnknownUser() : new Guest();
        }

        return $author;
    }

    /**
     * get content
     *
     * @return string
     */
    public function getContent()
    {
        if ($this->status === static::STATUS_TRASH || $this->approved === static::APPROVED_REJECTED) {
            return xe_trans('comment::RemoveContent');
        }
        
        return $this->content;
    }

    /**
     * check assented
     *
     * @return bool
     */
    public function isAssented()
    {
        return $this->voteType === 'assent';
    }

    /**
     * check dissent
     *
     * @return bool
     */
    public function isDissented()
    {
        return $this->voteType === 'dissent';
    }

    /**
     * set vote type
     *
     * @param string $type vote type
     *
     * @return void
     */
    public function setVoteType($type)
    {
        $this->voteType = $type;
    }

    /**
     * get target
     *
     * @return mixed|null
     */
    public function getTarget()
    {
        if ($target = $this->getRelationValue('target')) {
            return $target->commentable ?: $target;
        }

        return null;
    }

    /**
     * get display status name
     *
     * @param int $displayCode display status code
     *
     * @return string
     */
    public function getDisplayStatusName($displayCode)
    {
        $displayName = [
            self::DISPLAY_HIDDEN => 'comment::displayStatusHidden',
            self::DISPLAY_SECRET => 'comment::displayStatusSecret',
            self::DISPLAY_VISIBLE => 'comment::displayStatusVisible'
        ];

        return $displayName[$displayCode];
    }

    /**
     * get approve status name
     *
     * @param int $approveCode approve status code
     *
     * @return string
     */
    public function getApproveStatusName($approveCode)
    {
        $approveName = [
            self::APPROVED_REJECTED => 'comment::approveStatusRejected',
            self::APPROVED_WAITING => 'comment::approveStatusWaiting',
            self::APPROVED_APPROVED => 'comment::approveStatusApproved'
        ];

        return $approveName[$approveCode];
    }
}
