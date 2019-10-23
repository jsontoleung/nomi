// pages/article/detail/index.js
var app = getApp();
var WxParse = require('../../../wxParse/wxParse.js');
var util = require('../../../utils/util.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    refresh: 0,
    show: true,
    hovers: false,
    showBtn: false,
  },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function(options) {
        
        var that = this;
        var id = options.id;
        this.data.currentPostId = id;
        var refresh = 0; //换一批

        // 分享
        var artuid = options.artuid;
        if (id && artuid) {
            wx.redirectTo({
                url: "/pages/login/login?artuid="+artuid+"&artid="+id
            })
        }

        wx.request({
            url: app.data.getUrl + "/article/detail",
            method: 'post',
            data: {
                id: id,
                refresh: refresh,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function(res) {

                if (res.data.status == 1) {
                    
                    var id = res.data.id;
                    var art = res.data.art;
                    var lists = res.data.lists;
                    var click = res.data.click;
                    var comment = res.data.comment;
                    var content = art.content;                    
                    WxParse.wxParse('content', 'html', content, that, 3);
                    
                    that.setData({
                        id: options.id,
                        art: art,
                        lists: lists,
                        click: click,
                        comment: comment,
                        is_like: art.is_like,
                        collected: art.is_collect,
                    });

                } else {
                wx.showToast({
                    title: res.data.msg,
                    duration: 2000
                });
                }

            },
            fail: function(e) {
                wx.showToast({
                title: '网络异常',
                duration: 2000
                });
            },
        })

    },




    /**
     * 点击按钮弹窗
     */
    onLinkshow: function (e) {

        if (wx.getStorageSync('uid') == false) {

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
        else {
            this.setData({
                hovers: true,
                showBtn: true,
            })
        }
        
    },
    /**
     * 点击按钮弹窗
     */
    onLinkhide: function (e) {
        this.setData({
            hovers: false,
            showBtn: false,
        })
    },



    // 页面上拉触底事件(证明用户浏览文章)
    onReachBottom: function () {

        let that = this;
        wx.request({

            url: app.data.getUrl + "/article/play_num",
            method: 'post',
            data: {
                artid: this.data.currentPostId,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {

                if (res.data.status == 1) {
                    // wx.showToast({
                    //     title: res.data.msg,
                    //       icon: 'success',
                    //     duration: 1000
                    // })
                    console.log('欢迎观看');
                }

            }

        })

    },

    

    // 点赞
    onLikeTap: function (event) {

        if (wx.getStorageSync('uid') == false) {

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
        else {

            // 主播id
            var that = this;
            var voiceid = this.data.id;
            var is_like = event.currentTarget.dataset.type;

            wx.request({

                url: app.data.getUrl + "/article/like",
                method: 'post',
                data: {
                    voiceid: voiceid,
                    is_like: is_like,
                    uid: wx.getStorageSync('uid')
                },
                header: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },

                success: function (res) {

                    if (res.data.status == 1) {

                        that.setData({
                            is_like: res.data.is_like
                        })

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

        }

        

    },


  // 收藏
    onCollectionTap: function (event) {

        if (wx.getStorageSync('uid') == false) {

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
        else {

            // 产品id
            var that = this;
            var id = this.data.id;
            var collected = event.currentTarget.dataset.type;

            wx.request({

                url: app.data.getUrl + "/article/collect",
                method: 'post',
                data: {
                    id: id,
                    collected: collected,
                    uid: wx.getStorageSync('uid')
                },
                header: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },

                success: function (res) {

                    if (res.data.status == 1) {

                        that.setData({
                            collected: res.data.collected
                        })

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

        }

        

    },


  // 换一批
  _refresh: function(e) {

    var that = this;
    var id = that.data.id;
    var refresh = 1;

    wx.request({

      url: app.data.getUrl + "/article/detail",

      method: "post",

      data: {
        id: id,
        refresh: refresh,
        uid: wx.getStorageSync('uid')

      },

      header: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },

      success: function(res) {

        if (res.data.status == 1) {

          var art = res.data.art;
          var lists = res.data.lists;
          var comment = res.data.comment;

          that.setData({

            art: art,
            lists: lists,
            comment: comment,

          });

        } else {

          wx.showToast({
            title: res.data.msg,
            duration: 2000
          });

        }

      },

      fail: function(e) {

        wx.showToast({
          title: '网络异常',
          duration: 2000
        });

      },

    })

  },


  // 评论点赞
    onCommentLikeTap: function (e) {

        if (wx.getStorageSync('uid') == false) {

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
        else {

            var that = this;
            var id = that.data.id; //文章id
            var comid = e.currentTarget.dataset.id;  // 评论id
            var is_comlike = e.currentTarget.dataset.type;


            wx.request({

                url: app.data.getUrl + "/article/commentLike",
                method: 'post',
                data: {
                    id: id,
                    comid: comid,
                    is_comlike: is_comlike,
                    uid: wx.getStorageSync('uid')
                },
                header: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },

                success: function (res) {

                    if (res.data.status == 1) {

                        that.setData({
                            comment: res.data.comment,
                        })

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

        }
        

  },


    /**
    * 发表评论
    */
    formSubmit:function(e) {

        if (wx.getStorageSync('uid') == false) {

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
        else {

            var content = e.detail.value.content;
            var art_id = this.data.currentPostId;
            if (content == '') {
                wx.showToast({
                    title: '请输入内容',
                    duration: 2000
                })
            } else {

                wx.request({

                    url: app.data.getUrl + "/article/publish",

                    method: "post",

                    data: {
                        art_id: art_id,
                        content: content,
                        uid: wx.getStorageSync('uid')

                    },

                    header: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },

                    success: function (res) {

                        if (res.data.status == 1) {

                            wx.redirectTo({
                                url: "/pages/article/detail/index?id=" + art_id
                            });

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

            }

        }

        

    },



    // 分享
    onShareAppMessage: function (options) {
        
        let that = this;
        //自定义信息
        let sendinfo = {
            id: options.target.dataset.id,
            title: options.target.dataset.title,
            userid: wx.getStorageSync('uid')
        }
        let str = JSON.stringify(sendinfo);

        if (options.from == 'button') {
            console.log('按钮分享');
            that.shareReturn(sendinfo.id);
        }

        return {

            title: sendinfo.title,
            imageUrl: that.data.art.cover_detail,
            path: '/pages/article/detail/index?id=' + sendinfo.id + '&artuid=' + sendinfo.userid,
            // path: '/pages/index/index?shareid=' + sendinfo.userid,
            success: (res) => {

                wx.showToast({
                    title: '分享成功',
                    icon: 'success',
                    duration: 2000
                });

                console.log("分享success()");
                console.log("onShareAppMessage()==>>转发成功", res);

            },
            fali: function (res) {
                // 分享失败
                console.log("onShareAppMessage()==>>转发失败", res);
            }

        }

    },


    // 分享回调
    shareReturn: function (id) {

        wx.request({

            url: app.data.getUrl + "/article/share",
            method: 'post',
            data: {
                id: id,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {
                    
                    console.log('回调成功');

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