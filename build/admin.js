(()=>{"use strict";jQuery(document).ready((function(t){let e=t('<a href="#" style="display:block; float:right; clear:right; margin:10px;">'+EM.open_text+"</a>");t("#em-options-title").before(e),e.on("click",(function(e){e.preventDefault(),t(this).text()==EM.close_text?(t(".postbox").addClass("closed"),t(this).text(EM.open_text)):(t(".postbox").removeClass("closed"),t(this).text(EM.close_text))})),t(".tabs-active .nav-tab-wrapper .nav-tab").on("click",(function(){let a=t(this).attr("id");t(".em-menu-group").hide(),t("."+a).show(),t(".postbox").addClass("closed"),e.text(EM.open_text)})),t(".nav-tab-wrapper .nav-tab").on("click",(function(){t(".nav-tab-wrapper .nav-tab").removeClass("nav-tab-active").blur(),t(this).addClass("nav-tab-active")})),document.location.toString(),t(".nav-tab-link").on("click",(function(){t(t(this).attr("rel")).trigger("click")})),t('input[type="submit"]').on("click",(function(){let e=t(this).parents(".postbox").first(),a=document.location.toString().split("#"),o=a[0];if(a.length>1){let t=a[1].split("+")[0];e.attr("id")&&(t=t+"+"+e.attr("id").replace("em-opt-","")),o=o+"#"+t}document.location=o}))}))})();