// pages/order/master/index.js
var app = getApp();
var util = require('../../../utils/util.js');
var statusArrs = []
Page({

    /**
     * 页面的初始数据
     */
    data: {
        type: 1,
        list:[],
        qrcodeType:true,
        qrcode:'',
        showArr:  statusArrs
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;

        var type = options.type;

        util.isLogin(wx.getStorageSync('uid'));

        wx.request({

            url: app.data.getUrl + "/order/appint",

            method: 'post',

            data: {
                type: type,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

              var list = res.data.appint;
              that.setData({
                list: list
              });
              var z=0;
              for(var i=0;i<list.length;i++){
                for (var j = 0; j < list[i].sub.length;j++){
                    statusArrs[z] = true;
                    z++;
                  }
              }
              that.setData({
                showArr: statusArrs
              })
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
     * 生命周期函数--监听页面初次渲染完成
     */
    onReady: function () {

    },

    /**
     * 生命周期函数--监听页面显示
     */
    onShow: function () {

    },

    /**
     * 生命周期函数--监听页面隐藏
     */
    onHide: function () {

    },

    /**
     * 生命周期函数--监听页面卸载
     */
    onUnload: function () {

    },

    /**
     * 页面相关事件处理函数--监听用户下拉动作
     */
    onPullDownRefresh: function () {

    },

    /**
     * 页面上拉触底事件的处理函数
     */
    onReachBottom: function () {

    },

    /**
     * 用户点击右上角分享
     */
    onShareAppMessage: function () {

    },
    /**
     * 点击详情
     */
   detailAppint:function(e){
     var that = this;
     var num = e.currentTarget.dataset.index
     statusArrs[num] = !statusArrs[num]
     console.log(num)
     that.setData({
       showArr: statusArrs
     })
   },
    /**
     * 修改预约
     */
    onUpDateP: function (e) {
        var order_sn = e.currentTarget.dataset.orderSn;
        var update_type =1;//1修改，2再次预约
        wx.navigateTo ({
          url: "/pages/user/appointment_update/index?order_sn=" + order_sn + '&type=1'
        })
    },
    /**
     * 再次预约
     */
    onApps: function (e) {
      var order_sn = e.currentTarget.dataset.orderSn;
      var update_type = 2;//1修改，2再次预约
      wx.redirectTo ({
        url: "/pages/user/appointment_update/index?order_sn=" + order_sn + '&type=2'
      })
    },
  /**
  * 再次购买
  */
  onBuy: function (e) {
    var proid = e.currentTarget.dataset.pro;
    wx.navigateTo({
      url: "/pages/product/detail/index?pro_id=" + proid
    })
  },
  // 预约完成
  onAppSu: function (e) {
    var that=this;
    var order_sn = e.currentTarget.dataset.orderSn;
    util.isLogin(wx.getStorageSync('uid'));
    wx.request({
      url: app.data.getUrl + "/order/getqrcode",
      method: 'post',
      data: {
        order_sn: order_sn,
        uid: wx.getStorageSync('uid')
      },
      header: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      success: function (res) {
        if(res.data.code==0){
          that.setData({
            qrcodeType: false,
            qrcode: res.data.qrcode
          });
        }else{
            
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
  // 隐藏二维码
  onHideQrcode: function (e) {
    var that = this;
    that.setData({
      qrcodeType: true
    });
  },
})