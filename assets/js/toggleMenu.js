var CommentToggleMenu = {
  // 댓글 삭제
  delete: function(e, id) {
    e.preventDefault();

    var $commentItem = $('[data-id="' + id + '"]');
    $commentItem.find('.__xe_comment_btn_destroy').trigger('click');
  },

  // 댓글 수정
  update: function(e, id) {
    e.preventDefault();

    var $commentItem = $('[data-id="' + id + '"]');
    $commentItem.find('.__xe_comment_btn_edit').trigger('click');
  }
};