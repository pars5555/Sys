var Modals = {
    showConfirmDlg: function (headline, msg, hiddenVal, hiddenTmpVal, onConfirm, onCancel) {
        $('#confirm_modalBox').addClass('is_active');
        $('#confirm_modalBox').find('#confirm_dlg_headline').text(headline);
        $('#confirm_modalBox').find('#confirm_dlg_message').text(msg);
        $('#confirm_modalBox').find('#confirm_item_id').val(hiddenVal);
        $('#confirm_modalBox').find('#confirm_tmp_val').val(hiddenTmpVal);
        $('#popup_confirm_btn').off();
        $('#popup_cancel_btn').off();
        $('#popup_close_btn').off();
        $('#popup_confirm_btn').one('click', function () {
            $('#confirm_modalBox').removeClass('is_active');
            onConfirm($('#confirm_modalBox').find('#confirm_item_id').val());
        });
        $('#popup_cancel_btn, #popup_close_btn').one('click', function () {
            $('#confirm_modalBox').removeClass('is_active');
            onCancel($('#confirm_modalBox').find('#confirm_tmp_val').val());
        });
    },
    showDlg: function (id, onConfirm, onCancel) {
        $('#'+ id).addClass('is_active');
        $('#'+ id).find('.f_confirm-modal').off();
        $('#'+ id).find('.f_cancel-modal').off();        
        
        $('#'+ id).find('.f_confirm-modal').one('click', function () {
            $('#'+ id).removeClass('is_active');
            onConfirm();
        });
        $('#'+ id).find('.f_cancel-modal').one('click', function () {
            $('#'+ id).removeClass('is_active');
            onCancel();
        });
    }
}
