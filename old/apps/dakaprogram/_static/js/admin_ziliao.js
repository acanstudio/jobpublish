editZiliaoSort = function(id) {
    var newsid = $("#sortid_" + id).val();
    $.post(U('dakaprogram/AdminHuati/editZiliaoSort'), {
        id: id,
        sort_id: newsid
    }, function(data) {
        if (data.status == 0) {
            ui.error(data.msg);
            location.reload();
        } else {
            //admin.ajaxReload(data.msg);
            ui.success(data.msg);
            location.reload();
        }
    }, 'json');
};
delZiliao = function(c,huati_id) {
    ui.confirm("是否确认操作？", {
        yes: function() {
            $.post(U('dakaprogram/AdminHuati/delZiliao'), {
                id: c
            }, function(data) {
                if (data.status == 0) {
                    ui.success(data.msg);
                    location.reload();
                   // location.href = '/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist&tabHash=huatilist';
                } else {
                    ui.error(data.msg);
                    location.reload();
                   // location.href = '/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist&tabHash=huatilist';
                }
                //admin.ajaxReload(data.msg);
            }, 'json');
        }
    });
};
delYinYue = function(c,huati_id) {
    ui.confirm("是否确认操作？", {
        yes: function() {
            $.post(U('dakaprogram/AdminHuati/delZiliao'), {
                id: c
            }, function(data) {
                if (data.status == 0) {
                    ui.success(data.msg);
                    location.reload();
                    //location.href = '/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist&tabHash=huatilist';
                } else {
                    ui.error(data.msg);
                    location.reload();
                    //location.href = '/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist&tabHash=huatilist';
                }
                //admin.ajaxReload(data.msg);
            }, 'json');
        }
    });
};
addZiliaoTreeCategory = function(id)
{
  if(typeof id === "undefined") {
    return false;
  }
  ui.box.load(U('dakaprogram/AdminHuati/addZiliaoHtml')+'&id='+id, "添加资料");
  return false;
};

addZiliaoYinyueTreeCategory = function(id)
{
  if(typeof id === "undefined") {
    return false;
  }
  ui.box.load(U('dakaprogram/AdminHuati/addZiliaoYinyueHtml')+'&id='+id, "添加音乐");
  return false;
};

disableAiZiliao = function(c) {
    ui.confirm("是否确认操作？", {
        yes: function() {
            $.post(U('dakaprogram/AdminHuati/disableAiziliao'), {
                id: c
            }, function(data) {
                if (data.status == 0) {
                    ui.success(data.msg);
                      location.reload();
                   // location.href = '/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist&tabHash=huatilist';
                } else {
                    ui.error(data.msg);
                      location.reload();
                  //  location.href = '/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist&tabHash=huatilist';
                }
                //admin.ajaxReload(data.msg);
            }, 'json');
        }
    });
};

setHuatiCategory = function(album_id)
{
  if(typeof album_id === "undefined") {
    return false;
  }
  ui.box.load(U('dakaprogram/AdminHuati/setHuatiCategoryHtml')+'&album_id=' + album_id, "设置分类");
  return false;
};
saveHuatiCategory = function(huati_id, id)
{
  if(typeof huati_id === "undefined") {
    return false;
  }
  ui.box.load(U('dakaprogram/AdminHuati/saveHuatiCategoryHtml')+'&huati_id=' + huati_id + '&id='+id, "添加分类");
  return false;
};
editHuatiCategorySort = function(id) {
    var newsid = $("#sortid_" + id).val();
    $.post(U('dakaprogram/AdminHuati/saveHuatiCategory'), {
        id: id,
        sort_id: newsid
    }, function(data) {
        if (data.status == 0) {
            ui.error(data.info);
            location.reload();
        } else {
            //admin.ajaxReload(data.msg);
            ui.success(data.info);
            location.reload();
        }
    }, 'json');
};
delHuatiCategory = function(id) {
    ui.confirm("是否确认操作？", {
        yes: function() {
            $.post(U('dakaprogram/AdminHuati/delHuatiCategory'), {
                id: id
            }, function(data) {
                if (data.status == 1) {
                    ui.success(data.info);
                    location.reload();
                } else {
                    ui.error(data.info);
                    location.reload();
                }
            }, 'json');
        }
    });
};
