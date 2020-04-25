$(function() {
    //自定义radio 模拟 switch样式
    $(".cb-enable").click(function() {
        var parent = $(this).parents('.onoff');
        $('.cb-disable', parent).removeClass('selected');
        $(this).addClass('selected');
        $('.checkbox', parent).prop('checked', true);
    });
    $(".cb-disable").click(function() {
        var parent = $(this).parents('.onoff');
        $('.cb-enable', parent).removeClass('selected');
        $(this).addClass('selected');
        $('.checkbox', parent).prop('checked', false);
    });
    
    //公共复选框点击切换样式
    $(".checkbox-common").on("click", function () {
        //如果样式是选中设置input为不选中
        if ($(this).hasClass('selected')) {
            $(this).children("input").prop("checked", false);
        }
        var checkbox = $(this).children("input");
        if (checkbox.is(":checked")) {
            $(this).addClass("selected");
        } else {
            $(this).removeClass("selected");
        }
    });
    //公共单选框点击切换样式
    $(".radio-common").on("click", function () {
        var radio = $(this).children("input");
        var name = radio.attr("name");
        if (radio.is(":checked")) {
            $(".radio-common>input[type='radio'][name='" + name + "']").parent().removeClass("selected");
            $(this).addClass("selected");
        } else {
            $(this).removeClass("selected");
        }
    });
    
});
$(function() {
    // 显示隐藏预览图 start
    $('.show_image').hover(
            function() {
                $(this).next().css('display', 'block');
            },
            function() {
                $(this).next().css('display', 'none');
            }
    );

    // 全选 start
    $('.checkall').click(function() {
        var _self = this.checked;
        $('.checkitem').each(function() {
            $(this).prop('checked', _self);
        });
        $('.checkall').prop('checked', _self);
    });

    // 表格鼠标悬停变色 start
    $("tbody tr").hover(
            function() {
                $(this).css({background: "#FBFBFB"});
            },
            function() {
                $(this).css({background: "#FFF"});
            });

    // 可编辑列（input）变色
    $('.editable').hover(
            function() {
                $(this).removeClass('editable').addClass('editable2');
            },
            function() {
                $(this).removeClass('editable2').addClass('editable');
            }
    );

    // 提示操作 展开与隐藏
    $("#checkZoom").click(function() {
        $(this).next("ul").toggle(800);
        $(this).find(".arrow").toggleClass("up");
    });

    // 可编辑列（area）变色
    $('.editable-tarea').hover(
            function() {
                $(this).removeClass('editable-tarea').addClass('editable-tarea2');
            },
            function() {
                $(this).removeClass('editable-tarea2').addClass('editable-tarea');
            }
    );

});


/**
 * Layer 通用ifram弹出窗口
 */
function dsLayerOpen(url, title,width,height) {
    if (!width)	width = '900px';
    if (!height) height = '500px';
    layer.open({
        type: 2,
        title: title,
        area: [width,height],
        fixed: false, //不固定
        maxmin: true,
        content: url
    });
}

/**
 * Layer 通用confirm弹出窗口
 * @param {type} url 链接地址
 * @param {type} msg 显示内容
 * @param {type} type 返回显示类型  默认未刷新当前页, Number类型为移除 id="ds_row_1" 中的内容  String类型为其他操作(reload\)
 * @returns {undefined}
 */
function dsLayerConfirm(url, msg,type) {
    layer.confirm(msg, {
        btn: ['确定', '取消'],
        title: false,
    }, function () {
        $.ajax({
            url: url,
            type: "get",
            dataType: "json",
            success: function (data) {
                layer.msg(data.message, {time: 1000}, function () {
                    if (data.code == 10000) {
                        if (typeof(type) == "undefined"){
                            location.reload();
                        }else if(typeof(type) == "number"){
                            $("#ds_row_"+type).remove();
                        }else if(typeof(type) == "string"){
                            if(type=="reload"){
                                location.reload();
                            }else{
                                
                            }
                        }else{
                            alert("类型错误");
                        }
                    }
                });
            }
        });
    });
}

/**
 * 批量删除
 */
function submit_delete_batch() {
    /* 获取选中的项 */
    var items = '';
    $('.checkitem:checked').each(function () {
        items += this.value + ',';
    });
    if (items != '') {
        items = items.substr(0, (items.length - 1));
        submit_delete(items);
    }else{
        layer.alert('请勾选选项', {icon: 2})  
    }
}

/**
 * 导出XLS
 * @param {type} url
 */
function export_xls(url)
{
    var inputs = $(".ds-search-form").find("input:text");
    var selects = $(".ds-search-form").find("select");
    var param = '';
    for (i = 0; i < inputs.length; i++)
    {
        if (inputs[i].name != 'controller' && inputs[i].name != 'action')
            param += "&" + inputs[i].name + "=" + $(inputs[i]).val();
    }
    for (i = 0; i < selects.length; i++)
    {
        param += "&" + selects[i].name + "=" + $(selects[i]).val();
    }
    location.href = url + param.replace("&", "?");
}