/*! For license information please see frontend-shortcut.js.LICENSE.txt */
!function(t){var e={};function r(a){if(e[a])return e[a].exports;var n=e[a]={i:a,l:!1,exports:{}};return t[a].call(n.exports,n,n.exports,r),n.l=!0,n.exports}r.m=t,r.c=e,r.d=function(t,e,a){r.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:a})},r.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},r.t=function(t,e){if(1&e&&(t=r(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var a=Object.create(null);if(r.r(a),Object.defineProperty(a,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var n in t)r.d(a,n,function(e){return t[e]}.bind(null,n));return a},r.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return r.d(e,"a",e),e},r.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},r.p="/dist/",r(r.s=6)}({"./src/frontend/frontend-shared.ts":function(t,e,r){"use strict";r.r(e),r.d(e,"PaypalPayments",(function(){return a}));class a{static scrollTop(){jQuery("html, body").animate({scrollTop:0},300)}static setNotices(t){jQuery(".woocommerce-notices-wrapper:first").html(t)}static makeRequest(t,e){const r={async:!0,crossDomain:!0,url:a.replaceVars(paypal_brasil_settings.paypal_brasil_handler_url,{ACTION:t}),method:"POST",dataType:"json",contentType:"application/json; charset=utf-8",data:JSON.stringify(e)};return jQuery.ajax(r)}static showDefaultButton(){jQuery("#paypal-brasil-button-container .default-submit-button").show(),jQuery("#paypal-brasil-button-container .paypal-submit-button").hide()}static showPaypalButton(){jQuery("#paypal-brasil-button-container .default-submit-button").hide(),jQuery("#paypal-brasil-button-container .paypal-submit-button").show()}static isPaypalPaymentsSelected(){return!!jQuery("#payment_method_paypal-brasil-spb-gateway:checked").length}static triggerUpdateCheckout(){jQuery(document.body).trigger("update_checkout")}static triggerUpdateCart(){jQuery(document.body).trigger("wc_update_cart")}static submitForm(){jQuery("form.woocommerce-checkout, form#order_review").submit()}static replaceVars(t,e){let r=t;for(let t in e)e.hasOwnProperty(t)&&(r=r.replace(new RegExp("{"+t+"}","g"),e[t]));return r}}},"./src/frontend/frontend-shortcut/frontend-shortcut-api.ts":function(t,e,r){"use strict";r.r(e),r.d(e,"paymentShortcut",(function(){return n}));var a=r("./src/frontend/frontend-shared.ts");const n={miniCart:{create:()=>new Promise((t,e)=>{a.PaypalPayments.makeRequest("shortcut",{nonce:paypal_brasil_settings.nonce}).done((function(e){t(e.data.ec)})).fail((function(t){e(t.responseJSON)}))}),approve:t=>{window.location=a.PaypalPayments.replaceVars(paypal_brasil_settings.checkout_review_page_url,{PAY_ID:t.paymentID,PAYER_ID:t.payerID})},error:t=>{t&&(a.PaypalPayments.setNotices(t.data.error_notice),a.PaypalPayments.scrollTop())},cancel:()=>{a.PaypalPayments.setNotices(paypal_brasil_shortcut_settings.cancel_message),a.PaypalPayments.scrollTop()}},cart:{create:()=>new Promise((t,e)=>{a.PaypalPayments.makeRequest("shortcut-cart",{nonce:paypal_brasil_settings.nonce}).done((function(e){t(e.data.ec)})).fail((function(t){e(t.responseJSON)}))}),approve:t=>{window.location=a.PaypalPayments.replaceVars(paypal_brasil_settings.checkout_review_page_url,{PAY_ID:t.paymentID,PAYER_ID:t.payerID})},error:t=>{t&&(a.PaypalPayments.setNotices(t.data.error_notice),a.PaypalPayments.scrollTop())},cancel:()=>{a.PaypalPayments.triggerUpdateCart(),a.PaypalPayments.setNotices(paypal_brasil_shortcut_settings.cancel_message),a.PaypalPayments.scrollTop()}}}},"./src/frontend/frontend-shortcut/frontend-shortcut.scss":function(t,e,r){},"./src/frontend/frontend-shortcut/frontend-shortcut.ts":function(t,e,r){"use strict";r.r(e);var a=r("./src/frontend/frontend-shared.ts"),n=r("./src/frontend/frontend-shortcut/frontend-shortcut-api.ts");class o extends a.PaypalPayments{constructor(){super(),jQuery("body").on("updated_shipping_method",this.renderCartButton).on("updated_wc_div",this.renderCartButton).on("updated_mini_cart",this.renderMiniCartButton),this.renderCartButton(),this.renderMiniCartButton()}renderMiniCartButton(){jQuery(".shortcut-button-mini-cart").each((t,e)=>{paypal.Buttons({locale:"pt_BR",style:{size:"responsive",color:paypal_brasil_settings.style.color,shape:paypal_brasil_settings.style.format,label:"buynow"},createOrder:n.paymentShortcut.miniCart.create,onApprove:n.paymentShortcut.miniCart.approve,onError:n.paymentShortcut.miniCart.error,onCancel:n.paymentShortcut.miniCart.cancel}).render(e)})}renderCartButton(){jQuery(".wc-proceed-to-checkout .shortcut-button").each((t,e)=>{paypal.Buttons({locale:"pt_BR",style:{size:"responsive",color:paypal_brasil_settings.style.color,shape:paypal_brasil_settings.style.format,label:"buynow"},createOrder:n.paymentShortcut.cart.create,onApprove:n.paymentShortcut.cart.approve,onError:n.paymentShortcut.cart.error,onCancel:n.paymentShortcut.cart.cancel}).render(e)})}}new o},6:function(t,e,r){r("./src/frontend/frontend-shortcut/frontend-shortcut.ts"),t.exports=r("./src/frontend/frontend-shortcut/frontend-shortcut.scss")}});
//# sourceMappingURL=frontend-shortcut.js.map