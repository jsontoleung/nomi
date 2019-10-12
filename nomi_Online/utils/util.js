function isLogin(uid) {

    if (uid == false) {

        wx.showModal({
            title: '您还没登陆',
            content: '您是否授权登陆',
            success: function (sm) {
                if (sm.confirm) {

                    wx.redirectTo({
                        url: "/pages/login/login"
                    })
                    return false;
                    
                } else if (sm.cancel) {
                    
                    return false;
                }
            }
        })

    }

}



module.exports = {
    isLogin: isLogin,
}