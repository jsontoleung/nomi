var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        num: 0,
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;

        var total = options.total;

        console.log(total);

        that.setData({

            total: total,

        });

    },



    /**
     * 全部提现
     */
    alExtract: function (e) {

        var num = e.currentTarget.dataset.num;
        console.log(num);
        this.setData({

            num: num,

        });


    },



    onExtract: function (e) {

        function isDot(num) {
            var result = (num.toString()).indexOf(".");
            if (result != -1) {
                return false;
            }
        } 

        var that = this;
        var money = e.detail.value.num;
        var total = that.data.total;
        
       
        if (money == false) {
            wx.showToast({
                title: '请选择金额',
                icon: 'none',
                duration: 1000
            })
            return false;
        } 
        else if(money > 5000) {
            wx.showToast({
                title: '当日金额最高5000',
                icon: 'none',
                duration: 1000
            })
            return false;
        }
        else if(money > total) {
            wx.showToast({
                title: '余额不足',
                icon: 'none',
                duration: 1000
            })
            return false;
        }
        // else if (isDot(money) == false) {
        //     wx.showToast({
        //         title: '余额必须是整数',
        //         icon: 'none',
        //         duration: 1000
        //     })
        //     return false;
        // }

        wx.showToast({
            title: '提现功能于10月8日开通',
            icon: 'none',
            duration: 2000
        })
        return false;

        wx.showModal({
            title: '提示',
            content: '提现将于10🈷️8日开放',
            success: function (sm) {
                if (sm.confirm) {

                    console.log('用户点击确定');
                    return false;

                } else if (sm.cancel) {
                    console.log('用户点击取消');
                    return false;
                }
            }
        })

        wx.request({

            url: app.data.getUrl + "/Extract/txFunc",

            method: 'post',

            data: {
                money: money,
                openid: wx.getStorageSync('openid'),
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    console.log(res);

                } else {

                    wx.showToast({

                        title: res.data.msg,

                        duration: 2000

                    });
                }

            },
            fail: function (e) {

                wx.showToast({

                    title: '网络异常',

                    duration: 2000

                });

            },

        })

    },

    
})