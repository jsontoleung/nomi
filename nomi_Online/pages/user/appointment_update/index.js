// pages/product/pay/index.js
var util = require('../../../utils/util.js');
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
      proInfo:'',
      buy_num:1,
      date:'',
      array: [],
      name: '',
      phone:'',
      apptime: '',
      type:'',
      timeDetail: true,
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        var that = this;

        var type = options.type;

        var order_sn = options.order_sn;

        util.isLogin(wx.getStorageSync('uid'));

        wx.request({

          url: app.data.getUrl + "/order/updateappint",

          method: 'post',

          data: {
            order_sn:order_sn,
            type: type,
            uid: wx.getStorageSync('uid')
          },

          header: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },

          success: function (res) {

            var list = res.data.appint;
            var stroe_data = res.data.stroe;
            that.setData({
              proInfo: list,
              type: type,
              phone: list.phone,
              name: list.name
            });
            var stroe_arr = [];
            for (var i = 0; i < stroe_data.length; i++) {
              var stroe_index = stroe_data[i].shop_name;
              var stroe_area = stroe_data[i].area;
              var stroe_datas = stroe_index + '(' + stroe_area + ')';
              stroe_arr.push(stroe_datas)
            }
            that.setData({
              array: stroe_arr,
            });

          },
          fail: function (e) {

            wx.showToast({

              title: '网络异常',

              duration: 2000

            });

          },

        })
    },
  //确定修改
  onUpdateApp:function(e){
      var that = this;
      // 产品id
      var proid = this.data.currentPostId;
      // openid
      var openid = wx.getStorageSync('openid');
      // 联系名称
      var name = e.currentTarget.dataset.name;
      // 联系电话
      var phone = e.currentTarget.dataset.phone;
      // 线下门店
      var dian = e.currentTarget.dataset.dian;
      // 到店日期
      var dates = e.currentTarget.dataset.date;
      // 到店时间
      var apptime = e.currentTarget.dataset.time;

      if (name == null || name == 'undifind' || name == '') {
          wx.showToast({
              title: '请填写联系名称',
              icon: 'none',
              duration: 1500
          })
          return false;
      }
      if (phone == null || phone == 'undifind' || phone == '') {
        wx.showToast({
          title: '请填写联系电话',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
      if (!(/^1[34578]\d{9}$/.test(phone))) {
        wx.showToast({
          title: '联系电话格式不对',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
      if (dian == null || dian == 'undifind' || dian == '') {
        wx.showToast({
          title: '请选择门店',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
      if (dates == null || dates == 'undifind' || dates == '') {
        wx.showToast({
          title: '请选择到店时间',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
      if (apptime == null || apptime == 'undifind' || apptime == '') {
          wx.showToast({
              title: '请选择到店时间',
              icon: 'none',
              duration: 1500
          })
          return false;
      }
      console.log(e);
     var order_sn = e.currentTarget.dataset.orderSn;
     var type = e.currentTarget.dataset.type;
     var proid = e.currentTarget.dataset.proid;
      wx.request({
        url: app.data.getUrl + "/order/updateappdate",
        method: 'post',
        data: {
          order_sn: order_sn,
          type: type,
          uid: wx.getStorageSync('uid'),
          phone:phone,
          name:name,
          stroe: dian,
          dates:dates,
          apptime: apptime,
          proid: proid
        },
        header: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        success: function (res) {
          if (res.data.code==0){
                wx.showToast({
                  title: '修改成功',
                  icon: 'none',
                  duration: 1500
                })
                wx.navigateTo({
                  url: "/pages/user/appointment/index"
                })
            }else{
              wx.showToast({
                title: '修改失败',
                icon: 'none',
                duration: 1500
              })
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
  //确定预约
  onYesApp: function (e) {
      var that = this;
      // 产品id
      var proid = this.data.currentPostId;
      // openid
      var openid = wx.getStorageSync('openid');
      // 联系名称
      var name = e.currentTarget.dataset.name;
      // 联系电话
      var phone = e.currentTarget.dataset.phone;
      // 线下门店
      var dian = e.currentTarget.dataset.dian;
      // 到店日期
      var dates = e.currentTarget.dataset.date;
      // 到店时间
      var apptime = e.currentTarget.dataset.time;
      
      if (name == null || name == 'undifind' || name == '') {
          wx.showToast({
              title: '请填写联系名称',
              icon: 'none',
              duration: 1500
          })
          return false;
      }
      if (phone == null || phone == 'undifind' || phone == '') {
        wx.showToast({
          title: '请填写联系电话',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
      if (!(/^1[34578]\d{9}$/.test(phone))) {
        wx.showToast({
          title: '联系电话格式不对',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
      if (dian == null || dian == 'undifind' || dian == '') {
        wx.showToast({
          title: '请选择门店',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
      if (dates == null || dates == 'undifind' || dates == '') {
        wx.showToast({
          title: '请选择到店时间',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
      if (apptime == null || apptime == 'undifind' || apptime == '') {
          wx.showToast({
              title: '请选择到店时间',
              icon: 'none',
              duration: 1500
          })
          return false;
      }
      console.log(e)
      var order_sn = e.currentTarget.dataset.orderSn;
      var type = e.currentTarget.dataset.type;
      var proid = e.currentTarget.dataset.proid;
      wx.request({
        url: app.data.getUrl + "/order/updateappdate",
        method: 'post',
        data: {
          order_sn: order_sn,
          type: type,
          uid: wx.getStorageSync('uid'),
          phone: phone,
          name: name,
          stroe: dian,
          dates: dates,
          apptime: apptime,
          proid: proid
        },
        header: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        success: function (res) {
          if (res.data.code ==0) {
            wx.showToast({
              title: '预约成功',
              icon: 'none',
              duration: 1500
            })
            wx.navigateTo({
              url: "/pages/user/appointment/index"
            })
          } else {
            wx.showToast({
              title: '预约失败',
              icon: 'none',
              duration: 1500
            })
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
  // 返回
  onBlackPage: function (e) {
    wx.navigateBack({
      delta: 1,
    });//返回上一页面
  },
  // 选择到店时间
  bindDateChange: function (e) {
    console.log('picker发送选择改变，携带值为', e.detail.value)
    this.setData({
      date: e.detail.value
    })
  },
  // 选择线下门店
  bindPickerChange: function (e) {
    console.log('picker发送选择改变，携带值为', e.detail.value)
    this.setData({
      index: e.detail.value
    })
  },
    // 输入联系名称
    nameInput: function (e) {
        var name = e.detail.value
        this.setData({
            name: name
        })
    },
  // 输入手机号码
    phoneInput: function (e) {
    var phone = e.detail.value
    this.setData({
      phone: phone
    })
  },


    // 显示时间
    showCostDetailFun(e) {

        var that = this;
        // 到店日期
        var dates = e.currentTarget.dataset.date;
        
        wx.request({

            url: app.data.getUrl + "/Payment/makeTime",
            data: {
                dates: dates,
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            method: 'POST',
            success: function (res) {

                if (res.data.status == 1) {

                    that.setData({
                        timeDetail: false,
                        timeArr: res.data.timeArr,
                    });

                }

            },
            fail: function (res) {
                console.log(res)
            }

        });

        // 显示遮罩层
        var animation = wx.createAnimation({
            duration: 200,
            timingFunction: "linear",
            delay: 0
        })
        animation.translateY(600).step()
        this.setData({
            animationData: animation.export(),
            showCostDetail: true
        })
        setTimeout(function () {
            animation.translateY(0).step()
            this.setData({
                animationData: animation.export()
            })
        }.bind(this), 200)
    },
    // 隐藏时间
    hideCostDetailFun(e) {
        this.setData({
            timeDetail: true
        })
        // 隐藏遮罩层
        var animation = wx.createAnimation({
            duration: 200,
            timingFunction: "linear",
            delay: 0
        })
        animation.translateY(600).step()
        this.setData({
            animationData: animation.export(),
        })
        setTimeout(function () {
            animation.translateY(0).step()
            this.setData({
                // animationData: animation.export(),
                showCostDetail: false
            })
        }.bind(this), 200)
    },

    selectTime: function (event) {
        //为下半部分的点击事件
        this.setData({
            currentTime: event.currentTarget.dataset.tindex,
            valueTime: event.currentTarget.dataset.time
        })
    },

    // 保存预约时间
    onPreservation: function (e) {
        var that = this;
        var apptime = e.currentTarget.dataset.value;
        this.setData({
            apptime: apptime
        })
        that.hideCostDetailFun();
    },
})