// pages/product/area/index.js
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        region: ['广东省', '广州市', '海珠区'],
        customItem: '全部',
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;

        wx.request({

            url: app.data.getUrl + "/product/address",

            method: 'post',

            data: {
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                var lists = res.data.lists;

                if (res.data.status == 1) {

                    that.setData({

                        lists: lists,

                    });

                } else {

                    wx.showToast({

                        title: "错误异常",

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



    /**
   * 选择区域事件
   */
    bindRegionChange(e) {
        // console.log('picker发送选择改变，携带值为', e.detail.value)
        this.setData({
            region: e.detail.value
        })
    },


    /**
   * 保存添加信息
   */
    formSubmit: function (e) {
        
        var that = this;

        if (e.detail.value.areas[0] == "") {
            wx.showToast({
                title: '请选择区域再保存',
                icon: 'loading',
                duration: 1500
            })
            return false;
        }

        function isInteger(obj) {
            return obj % 1 === 0
        }

        if (isInteger(e.detail.value.phone) == true) {
            if (e.detail.value.phone.length !== 11) {
                wx.showToast({
                    title: '请填写正确的手机号',
                    icon: 'loading',
                    duration: 1500
                })
                return false;
            }
        } 
        else {
            wx.showToast({
                title: '手机号格式不正确',
                icon: 'loading',
                duration: 1500
            })
            return false;
        }

        wx.request({
            url: app.data.getUrl + "/product/saveAddress",
            header: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            method: "post",
            data: {
                addr_id: e.detail.value.addr_id,
                name: e.detail.value.name,
                phone: e.detail.value.phone,
                areas: e.detail.value.areas,
                address: e.detail.value.address,
                uid: wx.getStorageSync('uid'),
            },
            success: function (res) {
                if (res.data.status == 1) {
                    wx.showToast({
                        title: '保存成功',
                        icon: 'success',//这里打印出登录成功
                        duration: 1000
                    })
                    // setTimeout(function () {
                    //     wx.redirectTo({
                    //         url: "/pages/product/pay/index?proid=" + that.data.pro_id
	                //     })
                    // }, 1000) //延迟时间 这里是1秒
                } else {
                    wx.showToast({
                        title: '保存失败',
                        icon: 'loading',
                        duration: 1500
                    })
                }
            }
        })

    },


    /** 获取地址 */
    // getArea() {
    //     var that = this;
    //     wx.getSetting({
    //         success(res) {
    //             console.log("vres.authSetting['scope.address']：", res.authSetting['scope.address'])
    //             if (res.authSetting['scope.address']) {
    //                 console.log("111")
    //                 wx.chooseAddress({
    //                     success(res) {
    //                         console.log(res.userName)
    //                         console.log(res.postalCode)
    //                         console.log(res.provinceName)
    //                         console.log(res.cityName)
    //                         console.log(res.countyName)
    //                         console.log(res.detailInfo)
    //                         console.log(res.nationalCode)
    //                         console.log(res.telNumber)
    //                         var lists = {
    //                             name: res.userName,
    //                             phone: res.telNumber,
    //                             areas: res.provinceName + '-' + res.cityName + '-' + res.countyName,
    //                             address: res.detailInfo,
    //                         }
    //                         console.log(lists);
    //                         that.setData({

    //                             lists: lists,

    //                         });
    //                     }
    //                 })
    //                 // 用户已经同意小程序使用录音功能，后续调用 wx.startRecord 接口不会弹窗询问

    //             } else {
    //                 if (res.authSetting['scope.address'] == false) {
    //                     console.log("222")
    //                     wx.openSetting({
    //                         success(res) {
    //                             console.log(res.authSetting)

    //                         }
    //                     })
    //                 } else {
    //                     console.log("eee")
    //                     wx.chooseAddress({
    //                         success(res) {
    //                             console.log(res.userName)
    //                             console.log(res.postalCode)
    //                             console.log(res.provinceName)
    //                             console.log(res.cityName)
    //                             console.log(res.countyName)
    //                             console.log(res.detailInfo)
    //                             console.log(res.nationalCode)
    //                             console.log(res.telNumber)
    //                             var lists = {
    //                                 name: res.userName,
    //                                 phone: res.telNumber,
    //                                 areas: res.provinceName + '-' + res.cityName + '-' + res.countyName,
    //                                 address: res.detailInfo,
    //                             }
    //                             console.log(lists);
    //                             that.setData({

    //                                 lists: lists,

    //                             });

    //                         }
    //                     })
    //                 }
    //             }
    //         }
    //     })
    // },


})