/*global $,Ajax,Element,window*/

var php_self = document.location.href;
var title = document.title;
var trial = "";
var formula1 = "";
var formula2 = "";
var w1 = "";
var w2 = "";
var w3 = "";
var smooth = "";
var xrange = "formula";

function display(option) {
  var uid = option;
  var url = php_self + "?function=display&uid=" + uid;
  document.location = url;
}

function update_zoom(frm) {
  if (frm.xrange[0].checked) {
    xrange = "entire";
  } else {
    xrange = "formula";
  }
  if (trial.length > 1) {
  var param = 'trial=' + trial;
  param += '&W1=' + w1;
  param += '&W2=' + w2;
  param += '&W3=' + w3;
  param += '&formula1=' + formula1;
  param += '&formula2=' + formula2;
  param += '&smooth=' + smooth;
  param += '&xrange=' + xrange;
  var url = "curator_data/cal_index_check.php";
  Element.show('spinner');
  var tmp = new Ajax.Updater($('step2'), url, {method: 'post', postBody: param}, {
        onComplete : function() {
            $('step2').show();
            document.title = title;
            Element.hide('spinner');
        }
    });
  } 
}

function display2(option) {
  var uid = option;
  document.getElementById("step2").innerHTML = "";
  var url = php_self + "?function=display&uid=" + uid;
  var tmp = new Ajax.Updater($('step2'), url, {
        onComplete : function() {
            $('step2').show();
            document.title = title;
        }
    });
}

function update_trial() {
  var e = document.getElementById("trial");
  trial = e.options[e.selectedIndex].value;
  var url = php_self + "?function=selTrial&trial=" + trial;
  /*var tmp = new Ajax.Updater($('step2'), url, {
        onComplete : function() {
            $('step2').show();
            document.title = title;
        }
    });
  */
}

function update_w1() {
  var test = document.getElementById("W1").value;
  if (isNaN(test)) {
    window.alert("value must be a number");
    document.getElementById("W1").value = "";
  } else {
    w1 = document.getElementById("W1").value;
  }
}

function update_w2() {
  var test = document.getElementById("W2").value;
  if (isNaN(test)) {
    window.alert("value must be a number");
    document.getElementById("W2").value = "";
  } else {
    w2 = document.getElementById("W2").value;
  }
}

function update_w3() {
  var test = document.getElementById("W3").value;
  if (isNaN(test)) {
    window.alert("value must be a number");
    document.getElementById("W3").value = "";
  } else {
    w3 = document.getElementById("W3").value;
  }
}

function update_f1() {
  var desc = "";
  var e = document.getElementById("formula1");
  formula1 = e.options[e.selectedIndex].value;
  w3 = "";
  if (formula1 == "NWI1") {
    w2 = 970;
    w1 = 900;
    formula2 = "(W2-W1)/(W1+W2)";
    desc = "Normalized Water Index";
  } else if (formula1== "NWI3") {
    w2 = 970;
    w1 = 880;
    formula2 = "(W2-W1)/(W1+W2)";
    desc = "Normalized Water Index";
  } else if (formula1 == "NDVI") {
    w2 = 900;
    w1 = 680;
    formula2 = "(W2-W1)/(W1+W2)";
    desc = "<a target='_blank' href=http://en.wikipedia.org/wiki/Normalized_Difference_Vegetation_Index>Normalized Difference Vegetation Index</a>";
  } else if (formula1 == "NDVIR") {
    w2 = 780;
    w1 = 670;
    formula2 = "(W2-W1)/(W1+W2)";
    desc = "<a target='_blank' href=http://en.wikipedia.org/wiki/Normalized_Difference_Vegetation_Index>Red Normalized Difference Vegetation Index</a>";
  } else if (formula1 == "NDVIG") {
    w2 = 780;
    w1 = 550;
    formula2 = "(W2-W1)/(W1+W2)";
    desc = "<a target='_blank' href=http://en.wikipedia.org/wiki/Normalized_Difference_Vegetation_Index>Green Normalized Difference Vegetation Index</a>";
  } else if (formula1 == "SR") {
    w2 = 900;
    w1 = 680;
    formula2 = "(W2-W1)";
    desc = "Simple Ratio";
  } else if (formula1 == "OSAVI") {
    w2 = 800;
    w1 = 670;
    formula2 = "(1+0.16)*(W2-W1)/(W2+W1)";
    desc = "<a target='_blank' href=http://digital.csic.es/bitstream/10261/10635/1/26.pdf>Optimized Soil-Adjusted Vegetation Index</a>";
  } else if (formula1 == "TCARI") {
    w3 = 700;
    w2 = 670;
    w1 = 550;
    formula2 = "3*(W3-W2) - 0.2*(W3-W1)";
    desc = "<a target='_blank' href=http://digital.csic.es/bitstream/10261/10635/1/26.pdf>Transformed Chlorophyll Absorption Index</a>";
  }
  document.getElementById("W1").value = w1;
  document.getElementById("W2").value = w2;
  document.getElementById("W3").value = w3;
  document.getElementById("formula2").value = formula2;
  document.getElementById("formdesc").innerHTML = desc;
  formula2 = encodeURIComponent(formula2); 
}

function update_f2() {
  formula2 = document.getElementById("formula2").value;
  formula2 = encodeURIComponent(formula2);
}

function update_smooth() {
  var e = document.getElementById("smooth");
  smooth = e.options[e.selectedIndex].value;
  if (smooth == "0") {
    document.getElementById("smooth2").innerHTML = "no smoothing";
  } else if (smooth == "5") {
    document.getElementById("smooth2").innerHTML = "median value using a window of 11 samples";
  } else if (smooth == "10") {
    document.getElementById("smooth2").innerHTML = "median value using a window of 21 samples";
  }
}

function cal_index() {
  var param = 'trial=' + trial;
  param += '&W1=' + w1;
  param += '&W2=' + w2;
  param += '&W3=' + w3;
  param += '&formula1=' + formula1;
  param += '&formula2=' + formula2;
  param += '&smooth=' + smooth;
  param += '&xrange=' + xrange;
  var url = "curator_data/cal_index_check.php?trial=" + trial + "&W1=" + w1 + "&W2=" + w2 + "&formula1=" + formula1 + "&formula2=" + formula2;
  url = "curator_data/cal_index_check.php";
  Element.show('spinner');
  var tmp = new Ajax.Updater($('step2'), url, {method: 'post', postBody: param}, {
        onComplete : function() {
            $('step2').show();
            document.title = title;
            Element.hide('spinner');
        }
    });
}
