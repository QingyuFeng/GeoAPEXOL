
function generalPopup(msg)
{
 hstr = "<font face=\"Arial\">" + msg;
 hstr = hstr + "</font>";
 alert("generalPopup: " + msg);

// popup = new OpenLayers.Popup("general",
//          map.getCenter(),
//          new OpenLayers.Size(200,100),
//          msg,
 //         true);

//popup.autoSize = true;
// popup.panMapIfOutOfView = true;
 // popup.maxSize = OpenLayers.Size(200,200);

// map.addPopup(popup,true);
 
}

function popupBubble(msg)
{
 //       AutoSizeFramedCloudMaxSize = OpenLayers.Class(OpenLayers.Popup.FramedCloud, {
 //           'autoSize': true, 
 //           'maxSize': new OpenLayers.Size(200,200)
 //       });
 //       popupClass = AutoSizeFramedCloudMaxSize;
 //       popupContentHTML = msg;
 alert("popupBubble: " + msg);
}

function areaTooLarge(wid,hgt) 
{
 var hstr  = "<table border=\"1\" width=\"100%\"><tr>";
   hstr = hstr + "<td height=\"120px\" width=\"250px\" bgcolor=\"#CCFFFF\">";
   hstr = hstr + "<font face=\"Arial\">";
   hstr = hstr + "Area is too large to delineate.<br>Zoom into a smaller area less than 0.20 degrees by 0.20 degrees.<br>The current area is " + wid.toFixed(2) + " degrees by " + hgt.toFixed(2) + " degrees.";
  hstr = hstr + "</font>";
  hstr = hstr + "</td></tr></table>";

  var popup = new Popup();
  map.addOverlay(popup);

  popup.show(map.getView().getCenter(), hstr);
}
