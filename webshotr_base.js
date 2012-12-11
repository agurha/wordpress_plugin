var insertedjQuery = false;
var nextRequestId = 1;
var nextHoverId = 1;
var wsInitialWindowLoaded = false;
var WS_DEBUG_NORMAL = 0;
var WS_DEBUG_WARNING = 1;
var WS_DEBUG_ERROR = 2;
var WS_DEBUG_ALL = 10;
var WebShotrKey = "0000000000000";
var WebShotrSize = "large";
var WebShotrAnimationDelay = 1500;
var WebShotrAjaxRequestDelay = 4000;
var WebShotrDebug = "false";
var WebShotrDebugLevel = WS_DEBUG_ERROR;
var WebShotrHost = "www.webshotr.com";
var WebShotrHoverSelector = "a.webshotr";
var wsJQ = undefined;

function WSLoadjQuery() {
  if (typeof (wsJQ) == "undefined") {
    if (!insertedjQuery) {
      var a = (("https:" == document.location.protocol) ? "https" : "http");
      document.write('<script type="text/javascript" src="' + a + '://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"><\/script><script>wsJQ = jQuery.noConflict();<\/script>');
      insertedjQuery = true
    }
    setTimeout("WSLoadjQuery()", 250)
  } else {
    wsJQ(function (b) {
      wsJQ(window).ready(function () {
        wsJQ(WebShotrHoverSelector).live("mouseover", function (c) {
          HoverMouseover(c, this)
        });
        wsJQ(WebShotrHoverSelector).live("mouseout", function (c) {
          HoverMouseout(c, this)
        })
      })
    })
  }
}
function WSLoadParams() {
  var a = document.getElementsByTagName("script");
  var c = a[a.length - 1].src;
  var e = new RegExp("[?][^#]*", "i");
  var b = {};
  c = unescape(c);
  c = c.match(e);
  if (!c) {
    return
  }
  c = new String(c);
  c = c.substr(1, c.length - 1);
  var f = c.split("&");
  for (i = 0; i < f.length; i++) {
    var d = f[i].split("=");
    if (d.length == 2) {
      b[d[0]] = d[1]
    }
  }
  return b
}
function WSInitParams(a) {
  if (!a) {
    return
  }
  if (a.debug) {
    switch (a.debug) {
      case "all":
        WebShotrDebug = "true";
        WebShotrDebugLevel = WS_DEBUG_ALL;
        break;
      case "warning":
        WebShotrDebug = "true";
        WebShotrDebugLevel = WS_DEBUG_WARNING;
        break;
      case "error":
      case "true":
        WebShotrDebug = "true";
        WebShotrDebugLevel = WS_DEBUG_ERROR;
        break;
      default:
        WebShotrDebug = "false";
        break
    }
  }
  if (a.size) {
    switch (a.size) {
      case "micro":
      case "tiny":
      case "verysmall":
      case "small":
      case "large":
      case "xlarge":
        WebShotrSize = a.size;
        break;
      default:
        WebShotrSize = "large";
        break
    }
  }
  if (a.key) {
    WebShotrKey = a.key
  }
  if (a.ajaxdelay) {
    WebShotrAjaxRequestDelay = a.ajaxdelay * 1000
  }
  if (a.host) {
    WebShotrHost = a.host
  }

  if (a.hoverselector) {
    WebShotrHoverSelector = a.hoverselector
  }
}

function DelayLoadImgWithAnimation(e, k) {
  var d = wsJQ(e).offset();
  var j = d.left + (wsJQ(e).width() / 2);
  var a = d.top + (wsJQ(e).height() / 2);
  var b = (("https:" == document.location.protocol) ? "https" : "http");
  var h = wsJQ("#WebShotr_Thumbnail_" + k);
  var c = wsJQ('<span class="WebShotr_Loading" id="webShotr_' + k + '"><img height="16" width="16" style="vertical-align: middle;" src="https://s3.amazonaws.com/web.webshotr.com/Fading_lines.gif" /></span>');
  var g = wsJQ(h).find("img:first");
  var f = h.position();
  c.css("display", "none");
  c.css("position", "absolute");
  c.css("top", f.top);
  c.css("left", f.left);
  c.css("z-index", 998);
  c.css("cursor", "default");
  h.append(c);
  CenterElementOver(c, g);
  c.show();
  wsJQ(e).animate({
    opacity: 0.2
  }, WebShotrAnimationDelay, function () {
    DelayLoadImg(e, k)
  })
}
function CancelLoadAnimation(c, b) {
  var a = wsJQ("#webShotr_" + b);
  if (!a) {
    DebugPrint("There was no load animation to remove for this request.", WS_DEBUG_WARNING, wsJQ(c));
    return
  }
  a.remove();
  var d = wsJQ('img[requestId="' + b + '"]');
  if (!d) {
    DebugPrint("Couldn't find the img to set full opacity for this request.", WS_DEBUG_WARNING, wsJQ(c));
    return
  }
  d.fadeTo(WebShotrAnimationDelay, 1)
}
function DelayLoadImg(b, a) {
  wsJQ.ajax({
    url: wsJQ(b).attr("src"),
    dataType: "jsonp",
    success: function (e) {
      if (!e || e.success != "1") {
        DebugPrint("The server couldn't process the request.", WS_DEBUG_ERROR, wsJQ(b));
        CancelLoadAnimation(b, a);
        return
      }
      if (e.state == "WAIT") {
        var d = wsJQ('img[requestId="' + a + '"]');
        if (d.length > 0) {
          DebugPrint("The screenshot is not available yet, will retry.", WS_DEBUG_NORMAL, wsJQ(b));
          setTimeout(function () {
            DelayLoadImg(d, a)
          }, WebShotrAjaxRequestDelay)
        } else {
          DebugPrint("The screenshot is not available yet and the img is no longer in the DOM, cancelling ajax request. (" + a + ")", WS_DEBUG_NORMAL, wsJQ(b))
        }
      } else {
        if (e.state == "AVAILABLE") {
          CancelLoadAnimation(b, a);
          var d = wsJQ('img[requestId="' + a + '"]');
          if (d.length > 0) {
            var c = d.attr("src").replace("key:", "time:" + new Date().getTime() + "/key:");
            d.attr("src", c);
            d.load(function () {
              setTimeout(function () {
                CancelLoadAnimation(b, a)
              }, WebShotrAnimationDelay)
            })
          } else {
            DebugPrint("Screenshot is available but the img element wasn't found.", WS_DEBUG_ERROR, wsJQ(b))
          }
        }
      }
    },
    error: function (e, c, d) {
      DebugPrint("An error occurred while trying to either connect to the server or parse the returned json.", WS_DEBUG_ERROR, wsJQ(b));
      CancelLoadAnimation(b, a)
    },
    complete: function () {}
  })
}
function LoadImgIfNotAvailable(c, b) {
  var d = new RegExp("http[s]?://" + WebShotrHost + "*", "i");
  var a = wsJQ(c).attr("src").match(d);
  if (!a) {
    DebugPrint("Refusing to request image src for odd host.", WS_DEBUG_ERROR, wsJQ(c));
    return
  }
  wsJQ.ajax({
    url: wsJQ(c).attr("src"),
    dataType: "jsonp",
    success: function (e) {
      if (!e || e.success != "1") {
        DebugPrint("The server couldn't process the request.", WS_DEBUG_ERROR, wsJQ(c));
        CancelLoadAnimation(c, b);
        return
      }
      if (e.state == "WAIT") {
        DelayLoadImgWithAnimation(c, b)
      } else {
        if (e.state == "AVAILABLE") {}
      }
    },
    error: function (g, e, f) {
      DebugPrint("An error occurred while trying to either connect to the server or parse the returned json.", WS_DEBUG_ERROR, wsJQ(c));
      CancelLoadAnimation(c, b)
    },
    complete: function () {}
  })
}
function HoverMouseover(g, d) {
  var c = wsJQ(d).attr("href");
  var m = wsJQ(d).attr("hoverId");
  var k = g.pageX + 15;
  var j = g.pageY + 15;
  if (!c) {
    return
  }
  var f = new RegExp("http[s]?://*", "i");
  var n = c.match(f);
  if (!n) {
    return
  }
  var l = null;
  if (m) {
    l = wsJQ("#WebShotr_HoverLink_" + m);
    if (!l) {
      return
    }
    l.css("top", j);
    l.css("left", k);
    l.show();
    var a = wsJQ(l).find(".WebShotr_Loading");
    var h = wsJQ(l).find("img:first");
    CenterElementOver(a, h);
    return
  }
  var b = nextHoverId;
  nextHoverId = nextHoverId + 1;
  wsJQ(d).attr("hoverId", b);
  l = wsJQ('<div id="WebShotr_HoverLink_' + b + '" class="webshotr_hover"></div>');
  l.css("display", "none");
  l.css("position", "absolute");
  l.css("top", j);
  l.css("left", k);
  l.css("z-index", 900);
  l.css("background-color", "#eee");
  l.css("border", "3px solid #ddd");
  wsJQ("body").append(l);
  l.show();
  wsImage(c, {
    appendTo: "WebShotr_HoverLink_" + b
  })
}
function HoverMouseout(d, c) {
  var b = wsJQ(c).attr("hoverId");
  if (!b) {
    return
  }
  var a = wsJQ("#WebShotr_HoverLink_" + b);
  if (!a) {
    return
  }
  a.hide()
}
function CenterElementOver(a, c) {
  if (!a || !c) {
    return
  }
  var b = c.position();
  var e = b.top + (c.height() / 2);
  var d = b.left + (c.width() / 2);
  a.css("top", e - (a.height() / 2));
  a.css("left", d - (a.width() / 2))
}
function wsImage(a, j) {
  if (!a) {
    return false
  }
  if (!j) {
    j = {}
  }
  if (!j.size) {
    j.size = WebShotrSize
  }
  if (!j.key) {
    j.key = WebShotrKey
  }
  if (!j.imgClass) {
    j.imgClass = "webshotr"
  } else {
    j.imgClass = "webshotr " + j.imgClass
  }
  var g = nextRequestId;
  nextRequestId = nextRequestId + 1;
  j.requestId = g;
  j.url = a;
  var c = (("https:" == document.location.protocol) ? "https" : "http");
  var b = "http://api.webshotr.com/v1/c52ef68e-5af3-435f-b46b-5cb08c88e764/012f91c0bb2ac232dc24be4b402b46dd2272968c/png/?url=love.com&width=320&height=568&full_page=true&delay=1000&force=true&wrap=iphone5";
  var d = new RegExp("^(http://|https://)", "i");
  var h = a.match(d);
  if (!j.appendTo) {
    document.write('<span id="WebShotr_Thumbnail_' + j.requestId + '"><img src="' + b + '" class="' + j.imgClass + '" requestId="' + j.requestId + '" /></span>')
  } else {
    var e = wsJQ("#" + j.appendTo);
    if (e && e.length > 0) {
      e.append('<span id="WebShotr_Thumbnail_' + j.requestId + '"><img src="' + b + '" class="' + j.imgClass + '" requestId="' + j.requestId + '" /></span>')
    } else {
      DebugPrint("Unable to locate element '#" + j.appendTo + "' to append image to.", WS_DEBUG_ERROR, wsJQ(f))
    }
  }
  var f = wsJQ('img[requestId="' + j.requestId + '"]');
  wsJQ(f).load(function () {
    DebugPrint("Starting load for (" + j.requestId + ").", WS_DEBUG_WARNING);
    LoadImgIfNotAvailable(f, j.requestId)
  });
  return j
}
function DebugPrint(g, c, h) {
  if (WebShotrDebug == "false") {
    return
  }
  if (c != WebShotrDebugLevel && WebShotrDebugLevel != WS_DEBUG_ALL) {
    return
  }
  var f = wsJQ("#webShotr_debug");
  if (!f || f.length <= 0) {
    f = wsJQ('<div id="webShotr_debug"></div>');
    f.css("display", "block");
    f.css("position", "fixed");
    f.css("bottom", "0");
    f.css("right", "0");
    f.css("height", "250px");
    f.css("width", "250px");
    f.css("background-color", "#eee");
    f.css("border", "3px solid #ddd");
    f.css("overflow-y", "auto");
    f.css("font-size", "6pt");
    f.css("z-index", 999);
    wsJQ("body").append(f)
  }
  var d = g;
  if (h) {
    var b = wsJQ(h).attr("id");
    var a = wsJQ(h).attr("src");
    var e = wsJQ(h).attr("requestId");
    if (b) {
      d = "[id: " + b + "] " + d
    }
    if (a) {
      d = "[src: " + a + "] " + d
    }
    if (e) {
      d = "[requestId: " + e + "] " + d
    }
  }
  f.append(d + "<br /><hr />")
}
WSInitParams(WSLoadParams());
WSLoadjQuery();