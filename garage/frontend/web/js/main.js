$(function () {
    var footerline = $("#footer-line");
    footerline.css({"overflow":"hidden", "width": "100%"});
    footerline.wrapInner("<span>");
    footerline.find("span").css({"width":"50%", "display":"inline-block", "text-align":"center"});
    footerline.append(footerline.find("span").clone());
    footerline.wrapInner("<div>");
    footerline.find("div").css({"width":"200%"});
    var reset = function () {
	$(this).css({"margin-left":"0%"});
	$(this).animate({"margin-left":"-100%"}, 12000, 'linear', reset);
    };
    reset.call(footerline.find("div"));
});