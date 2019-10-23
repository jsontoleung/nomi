// pages/product/pay/index.js
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
      buy_num: 1,
      paytype: 1,//1购买服务，不存在购买商品
      date: '',
      array: [],
      name: '',
      phone: '',
      apptime: '',
      thisday:'',
      nextday:'',
      timeShow: true,
      timeDetail: true,
        
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        
      var buy_num = wx.getStorageSync('now_buy_num');

        var that = this;
        var proid = options.proid;

        this.data.currentPostId = proid;

        wx.request({
            url: app.data.getUrl + "/payment/home",
            method: 'post',
            data: {
                proid: proid,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {

                var addr = res.data.addr;
                var proInfo = res.data.proInfo;
                var stroe_data = res.data.stroe;

                that.setData({
                    //buy_num: res.data.buy_num,
                    addr: addr,
                    proInfo: proInfo,
                    //count: proInfo.price_after,
                    count: (proInfo.price_after * buy_num).toFixed(2),
                    buy_num: buy_num,
                    thisday: res.data.thisday,
                    nextday: res.data.nextyear,
                });
                var stroe_arr=[];
                for (var i = 0; i < stroe_data.length;i++){
                  var stroe_index = stroe_data[i].shop_name;
                  var stroe_area = stroe_data[i].area;
                  var stroe_datas = stroe_index+'('+stroe_area+')';
                  stroe_arr.push(stroe_datas)
                }
                that.setData({
                  array: stroe_arr,
                });
              
              console.log(stroe_data.length)

            },
            fail: function (e) {
                wx.showToast({
                    title: '网络异常',
                    duration: 2000
                });
            },
        })

    },


    /* 点击减号 */
    bindMinus: function (e) {

        var buy_num = this.data.buy_num;
        var count = e.currentTarget.dataset.count;

        // 如果大于1时，才可以减  
        if (buy_num > 1) {
            buy_num--;
            // count = Math.floor(count * 100) / 100 - Math.floor(this.data.proInfo.price_after * 100) / 100;
            count = count * 100 / 100 - this.data.proInfo.price_after * 100 / 100;
            count = count.toFixed(2);//保留小数点后两位
        }

        // 将数值与状态写回  
        this.setData({
            buy_num: buy_num,
            count: count,
        });

    },

    /* 点击加号 */
    bindPlus: function (e) {

        var buy_num = this.data.buy_num;
        var count = e.currentTarget.dataset.count;

        // 不作过多考虑自增1
        if (buy_num >= this.data.proInfo.inventory) {
            wx.showToast({
                title: '库存有限喔!',
                duration: 1000
            })
        } else {
            buy_num++;
            // count = Math.floor(count * 100) / 100 + Math.floor(this.data.proInfo.price_after * 100) / 100;
            count = count * 100 / 100 + this.data.proInfo.price_after * 100 / 100;
            count = count.toFixed(2);//保留小数点后两位
        }

        // 将数值与状态写回  
        this.setData({
            buy_num: buy_num,
            count: count
        });

    },

    /* 输入框事件 */
    bindManual: function (e) {

        var buy_num = e.detail.value;

        // 将数值与状态写回  
        this.setData({
            buy_num: buy_num
        });

    },


    /* 确认购买 */
    onBuyTap: function (e) {
        
        var that = this;

        if (that.data.addr == null || that.data.addr == 'undifind' || that.data.addr == '') {
            wx.showModal({
                title: '警告',
                content: '请填写地址',
                confirmText: '知道了',
                showCancel: false,
                success(res) {
                    if (res.confirm) {
                        return false;
                    } else if (res.cancel) {
                        return false;
                    }
                }
            })
        }

        // 购买商品数量
        var buyNum = e.currentTarget.dataset.buynum;
        // 购买总价格
        var count = e.currentTarget.dataset.count;
        // 产品id
        var proid = this.data.currentPostId;
        // openid
        var openid = wx.getStorageSync('openid');
        
        wx.request({

            url: app.data.getUrl + "/pay/pay",
            data: {
                proid: proid,
                buyNum: buyNum,
                count: count,
                openid: openid
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            method: 'POST',
            success: function (res) {

                if (res.data.status == 1) {

                    var order_sn = res.data.order_sn;

                    wx.requestPayment({

                        'nonceStr': res.data.data.nonceStr,
                        'package': res.data.data.package,
                        'paySign': res.data.data.paySign,
                        'timeStamp': res.data.data.timeStamp,
                        'signType': 'MD5',
                        'success': function (res) {

                            // requestPayment:ok
                            // requestPayment:fail
                            if (res.errMsg == 'requestPayment:ok') {

                                wx.showToast({
                                    title: '支付成功',
                                    icon: 'success',
                                    duration: 2000
                                });
                                setTimeout(function () {
                                    wx.navigateTo({
                                        url: "/pages/product/notify/index?order_sn=" + order_sn
                                    })
                                }, 2000) //延迟时间 这里是1秒

                            } else {

                                wx.showToast({
                                    title: '支付失效',
                                    duration: 2000
                                });

                            }

                        },
                        'fail': function (res) {
                            console.log(res);
                        },
                        'complete': function (res) {
                            console.log('complete----' + res);
                        }
                    });

                } else {

                    wx.showToast({
                        title: res.data.msg,
                        duration: 2000
                    });

                }

            },
            fail: function (res) {
                console.log(res)
            }

        });
        

    },
  // 服务购买
  onServeTap: function (e) {
    
    var that = this;

    // 购买商品数量
    var buyNum = e.currentTarget.dataset.buynum;
    // 购买总价格
    var count = e.currentTarget.dataset.count;
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

      if (buyNum == null || buyNum == '0' || buyNum == 0) {
        wx.showToast({
          title: '暂无服务',
          icon: 'none',
          duration: 1500
        })
        return false;
      }
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
          title: '请选择到店日期',
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
      wx.request({

        url: app.data.getUrl + "/pay/pay",
        data: {
          proid: proid,
          buyNum: buyNum,
          count: count,
          openid: openid,
          type: that.data.paytype,
          name: name,
          phone:phone,
          dian:dian,
          dates:dates,
          apptime:apptime
        },
        header: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        method: 'POST',
        success: function (res) {

          if (res.data.status == 1) {

            var order_sn = res.data.order_sn;

            wx.requestPayment({

              'nonceStr': res.data.data.nonceStr,
              'package': res.data.data.package,
              'paySign': res.data.data.paySign,
              'timeStamp': res.data.data.timeStamp,
              'signType': 'MD5',
              'success': function (res) {

                // requestPayment:ok
                // requestPayment:fail
                if (res.errMsg == 'requestPayment:ok') {

                  wx.showToast({
                    title: '支付成功',
                    icon: 'success',
                    duration: 2000
                  });
                  setTimeout(function () {
                    wx.navigateTo({
                      url: "/pages/product/notify/index?order_sn=" + order_sn
                    })
                  }, 2000) //延迟时间 这里是1秒

                } else {

                  wx.showToast({
                    title: '支付失效',
                    duration: 2000
                  });

                }

              },
              'fail': function (res) {
                console.log(res);
              },
              'complete': function (res) {
                console.log('complete----' + res);
              }
            });

          } else {

            wx.showToast({
              title: res.data.msg,
              duration: 2000
            });

          }

        },
        fail: function (res) {
          console.log(res)
        }

      });
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
        date: e.detail.value,
        timeShow: false
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