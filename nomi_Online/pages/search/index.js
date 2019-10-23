// pages/search/index.js
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        // 搜索框状态
        inputShowed: true,
        //显示结果view的状态
        viewShowed: false,
        // 搜索框值
        inputVal: "",
        //搜索渲染推荐数据
        catList: [],

        btnWidth: 300, //删除按钮的宽度单位
        startX: "", //收支触摸开始滑动的位置
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;
        //初始化界面
        that.initEleWidth();

    },


    // 隐藏搜索框样式
    hideInput: function () {
        this.setData({
            inputVal: "",
            inputShowed: false
        });
    },

    // 清除搜索框值
    clearInput: function () {
        this.setData({
            inputVal: ""
        });
    },

    // 键盘抬起事件2
    inputTyping: function (e) {
        console.log(e.detail.value);
        var that = this;
        if (e.detail.value == '') {
            wx.showToast({
                title: "请说点什么",
                duration: 2000
            });
        }
        that.setData({
            viewShowed: false,
            inputVal: e.detail.value
        });

        wx.request({
            url: app.data.getUrl + "/search/search",
            data: { "uid": wx.getStorageSync('uid'), "content": e.detail.value },
            method: 'POST',
            header: {
                'Content-type': 'application/json'
            },
            success: function (res) {
                console.info(res.data.result);
                that.setData({
                    result: res.data.result
                })
            }
        });
    },

    // 获取选中推荐列表中的值
    btn_name: function (res) {
        console.log(res.currentTarget.dataset.index, res.currentTarget.dataset.name);
        console.log(res.currentTarget.dataset.index, res.currentTarget.dataset.id);

        var that = this;

        that.hideInput();

        that.setData({
            viewShowed: true,
            carNum: res.currentTarget.dataset.name,
            deviceId: res.currentTarget.dataset.id
        });
    },

    //获取元素自适应后的实际宽度
    getEleWidth: function (w) {
        var real = 0;
        try {
            var res = wx.getSystemInfoSync().windowWidth;
            var scale = (750 / 2) / (w / 2); //以宽度750px设计稿做宽度的自适应
            real = Math.floor(res / scale);
            return real;
        } catch (e) {
            return false;
            // Do something when catch error
        }
    },
    initEleWidth: function () {
        var btnWidth = this.getEleWidth(this.data.btnWidth);
        this.setData({
            btnWidth: btnWidth
        });
    },

    
})