const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/BarcodePrinter.vue_vue_type_style_index_0_scoped_4fb4eefc_lang-4EuhrVZa.js","assets/app-hyZiV4Sx.js","assets/app-DPSgePyu.css","assets/BarcodePrinter-D2DmHTLH.css"])))=>i.map(i=>d[i]);
import{s as rt,r as f,o as dt,a as c,c as b,b as t,e as y,d as u,t as a,l as r,x as v,v as P,y as J,h as M,w as F,i as yt,u as Y,p as Z,j as tt,f as lt,F as nt,n as H,z as I,A as et,g as it,_ as wt}from"./app-hyZiV4Sx.js";import{_ as kt}from"./AuthenticatedLayout-BeBCZfZC.js";import{a as xt}from"./BarcodePrinter.vue_vue_type_style_index_0_scoped_4fb4eefc_lang-4EuhrVZa.js";import{_ as ct}from"./_plugin-vue_export-helper-DlAUqK2U.js";const _t={class:"barcode-printer"},$t=["disabled"],Pt={key:0,class:"spinner-border spinner-border-sm me-1"},St={class:"barcode-settings mt-2"},Ct={class:"mb-3"},Ut={class:"text-muted"},Vt={class:"row"},Bt={class:"col-md-6"},Nt={class:"text-muted"},zt={class:"col-md-6"},Gt={class:"text-muted"},Ot={class:"row mt-2"},jt={class:"col-md-3"},Tt={class:"form-check"},Ht={class:"col-md-3"},Dt={class:"form-check"},Mt={class:"col-md-3"},Wt={class:"form-check"},Lt={key:0,class:"row mt-3"},Jt={class:"col-md-6"},It={class:"col-md-6"},qt={class:"form-check mt-4"},Et={key:1,class:"row mt-2"},Rt={class:"col-md-6"},Qt={class:"form-check"},At={key:2,class:"alert alert-info mt-3"},Ft={class:"mb-0"},Xt={key:0,class:"barcode-preview mt-3"},Kt=["src"],Yt={__name:"BarcodePrinter",props:{barcodeData:{type:String,required:!0},productName:{type:String,default:""},translations:{type:Object,required:!0},printerSettings:{type:Object,default:()=>({width:2,height:30,type:"PNG"})}},setup(l){const h=rt(),_=l,p=f(!1),W=f(null),G=f(""),g=f(!1),n=f({width:2,height:70,fontSize:10,margin:2,landscape:!0,highQuality:!0,pageWidth:38,pageHeight:26,copies:1,showBarcodeNumber:!0,showPrice:!1,price:0}),O=()=>{g.value=!g.value},j=async()=>{if(!_.barcodeData){h.error("لا يوجد بيانات باركود للطباعة");return}p.value=!0;try{const d=C();await T(d)}catch(d){console.error("Print error:",d),h.error("حدث خطأ أثناء الطباعة")}finally{p.value=!1}},C=()=>{try{const d=document.createElementNS("http://www.w3.org/2000/svg","svg");xt(d,_.barcodeData,{format:"CODE128",width:n.value.width,height:n.value.height,displayValue:!1,margin:n.value.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const s=new XMLSerializer().serializeToString(d),i=new Blob([s],{type:"image/svg+xml;charset=utf-8"}),U=URL.createObjectURL(i);return"data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(s)))}catch(d){throw console.error("JsBarcode generation error:",d),d}},w=()=>{if(_.barcodeData)try{G.value=C()}catch(d){console.error("Preview update error:",d)}},T=async d=>{try{const s=window.open("","_blank","width=300,height=400"),i=_.productName||"Product",U=_.barcodeData,$=x(d,i,U);s.document.write($),s.document.close(),h.success("تم إرسال الباركود للطباعة")}catch(s){throw console.error("Direct print error:",s),s}},x=(d,s="Product",i="")=>{const U=n.value.landscape?"landscape":"portrait",$=`${n.value.pageWidth}mm`,V=`${n.value.pageHeight}mm`,X=n.value.landscape?`${$} ${V}`:`${V} ${$}`,K=n.value.landscape?"18mm":"22mm";let q="";for(let E=0;E<n.value.copies;E++)q+=`
  <div class="label-page">
    <div class="label-container">
      <div class="product-name">${s}</div>
      <img class="barcode-image" src="${d}" alt="Barcode">
      ${n.value.showBarcodeNumber?`<div class="barcode-text">${i}</div>`:""}
      ${n.value.showPrice&&n.value.price>0?`<div class="price-text">${n.value.price} ريال</div>`:""}
    </div>
  </div>
    `;return`<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركود</title>
  <style>
    /* Optimized for thermal printers like MHT-L58G */
    @page { 
      size: ${X}; 
      margin: 0; 
      orientation: ${U};
    }
    @media print {
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
    }
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }
    .label-page {
      width: ${$};
      height: ${V};
      margin: 0;
      padding: 0;
      overflow: hidden;
      page-break-after: always;
    }
    .label-container {
      width: ${$};
      height: ${V};
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 1mm;
      box-sizing: border-box;
    }
    .product-name {
      width: 100%;
      font-size: ${n.value.fontSize}px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 1mm;
      line-height: 1.1;
      max-height: 8mm;
      overflow: hidden;
      word-wrap: break-word;
    }
    .barcode-image {
      width: auto;
      max-width: 90%;
      height: auto;
      max-height: ${K};
      margin: 1mm auto;
      display: block;
      object-fit: contain;
    }
    .barcode-text {
      width: 100%;
      font-size: ${Math.max(n.value.fontSize-2,4)}px;
      font-family: monospace;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
    }
    .price-text {
      width: 100%;
      font-size: ${Math.max(n.value.fontSize-1,5)}px;
      font-family: Arial, sans-serif;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
      color: #dc3545;
    }
  </style>
</head>
<body>
  ${q}
  <script>
    window.onload = function() {
      setTimeout(function() {
        window.print();
        setTimeout(function() {
          window.close();
        }, 2000);
      }, 1500);
    }
  <\/script>
</body>
</html>`};return dt(()=>{_.barcodeData&&w()}),(d,s)=>(c(),b("div",_t,[t("button",{class:"btn btn-sm btn-success me-2",onClick:j,disabled:p.value||!l.barcodeData},[p.value?(c(),b("span",Pt)):y("",!0),s[9]||(s[9]=t("i",{class:"bi bi-printer"},null,-1)),u(" "+a(l.translations.print),1)],8,$t),t("div",St,[t("div",Ct,[s[10]||(s[10]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-files"}),u(" عدد النسخ")])],-1)),r(t("input",{type:"number",class:"form-control",min:"1",max:"100","onUpdate:modelValue":s[0]||(s[0]=i=>n.value.copies=i),placeholder:"أدخل عدد النسخ المطلوبة"},null,512),[[v,n.value.copies,void 0,{number:!0}]]),t("small",Ut,"سيتم طباعة "+a(n.value.copies)+" نسخة من الباركود",1)]),s[27]||(s[27]=t("hr",null,null,-1)),t("div",Vt,[t("div",Bt,[s[11]||(s[11]=t("label",{class:"form-label"},"ارتفاع الباركود",-1)),r(t("input",{type:"range",class:"form-range",min:"30",max:"150",step:"5","onUpdate:modelValue":s[1]||(s[1]=i=>n.value.height=i),onInput:w},null,544),[[v,n.value.height]]),t("small",Nt,a(n.value.height)+"px",1)]),t("div",zt,[s[12]||(s[12]=t("label",{class:"form-label"},"حجم الخط",-1)),r(t("input",{type:"range",class:"form-range",min:"6",max:"20",step:"1","onUpdate:modelValue":s[2]||(s[2]=i=>n.value.fontSize=i),onInput:w},null,544),[[v,n.value.fontSize]]),t("small",Gt,a(n.value.fontSize)+"px",1)])]),t("div",Ot,[t("div",jt,[t("div",Tt,[r(t("input",{class:"form-check-input",type:"checkbox",id:"landscapeMode","onUpdate:modelValue":s[3]||(s[3]=i=>n.value.landscape=i),onChange:w},null,544),[[P,n.value.landscape]]),s[13]||(s[13]=t("label",{class:"form-check-label",for:"landscapeMode"}," طباعة بالعرض (Landscape) ",-1))])]),t("div",Ht,[t("div",Dt,[r(t("input",{class:"form-check-input",type:"checkbox",id:"highQuality","onUpdate:modelValue":s[4]||(s[4]=i=>n.value.highQuality=i),onChange:w},null,544),[[P,n.value.highQuality]]),s[14]||(s[14]=t("label",{class:"form-check-label",for:"highQuality"}," جودة عالية ",-1))])]),t("div",Mt,[t("div",Wt,[r(t("input",{class:"form-check-input",type:"checkbox",id:"showBarcodeNumber","onUpdate:modelValue":s[5]||(s[5]=i=>n.value.showBarcodeNumber=i),onChange:w},null,544),[[P,n.value.showBarcodeNumber]]),s[15]||(s[15]=t("label",{class:"form-check-label",for:"showBarcodeNumber"}," عرض رقم الباركود ",-1))])]),t("div",{class:"col-md-3"},[t("button",{class:"btn btn-sm btn-info",onClick:O},s[16]||(s[16]=[t("i",{class:"bi bi-info-circle"},null,-1),u(" معلومات الطابعة ")]))])]),n.value.showPrice?(c(),b("div",Lt,[t("div",Jt,[s[17]||(s[17]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-currency-dollar"}),u(" سعر البيع")])],-1)),r(t("input",{type:"number",class:"form-control",min:"0",step:"0.01","onUpdate:modelValue":s[6]||(s[6]=i=>n.value.price=i),placeholder:"أدخل سعر البيع"},null,512),[[v,n.value.price,void 0,{number:!0}]])]),t("div",It,[t("div",qt,[r(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":s[7]||(s[7]=i=>n.value.showPrice=i),onChange:w},null,544),[[P,n.value.showPrice]]),s[18]||(s[18]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])):y("",!0),n.value.showPrice?y("",!0):(c(),b("div",Et,[t("div",Rt,[t("div",Qt,[r(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":s[8]||(s[8]=i=>n.value.showPrice=i),onChange:w},null,544),[[P,n.value.showPrice]]),s[19]||(s[19]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])),g.value?(c(),b("div",At,[s[26]||(s[26]=t("h6",null,[t("i",{class:"bi bi-printer"}),u(" معلومات الطابعة الحرارية:")],-1)),t("ul",Ft,[s[23]||(s[23]=t("li",null,[t("strong",null,"الطابعة:"),u(" Thermal Printer (MHT-L58G)")],-1)),t("li",null,[s[20]||(s[20]=t("strong",null,"حجم الورق المحدد:",-1)),u(" "+a(n.value.pageWidth)+"mm × "+a(n.value.pageHeight)+"mm",1)]),t("li",null,[s[21]||(s[21]=t("strong",null,"الاتجاه:",-1)),u(" "+a(n.value.landscape?"أفقي (Landscape)":"عمودي (Portrait)"),1)]),t("li",null,[s[22]||(s[22]=t("strong",null,"حجم الطباعة:",-1)),u(" "+a(n.value.landscape?`${n.value.pageWidth}mm × ${n.value.pageHeight}mm`:`${n.value.pageHeight}mm × ${n.value.pageWidth}mm`),1)]),s[24]||(s[24]=t("li",null,[t("strong",null,"دقة الطباعة الموصى بها:"),u(" 203 DPI")],-1)),s[25]||(s[25]=t("li",null,"⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة",-1))])])):y("",!0)]),G.value?(c(),b("div",Xt,[t("img",{src:G.value,alt:"Barcode Preview",class:"img-fluid border"},null,8,Kt)])):y("",!0),t("canvas",{ref_key:"barcodeCanvas",ref:W,style:{display:"none"}},null,512)]))}},st=ct(Yt,[["__scopeId","data-v-4fb4eefc"]]),Zt={class:"pagetitle dark:text-white"},te={class:"dark:text-white"},ee={class:"breadcrumb"},se={class:"breadcrumb-item"},oe={class:"breadcrumb-item active dark:text-white"},ae={class:"section dashboard"},le={class:"row"},ne={class:"col-12"},ie={class:"card"},re={class:"card-body"},de={class:"row"},ce={class:"col-md-4"},ue=["placeholder"],me={class:"col-md-2"},be={type:"submit",class:"btn btn-primary"},he={class:"col-md-3"},pe=["disabled"],ve={class:"col-md-3"},ge={class:"col-12"},fe={class:"card"},ye={class:"card-body"},we={class:"table-responsive"},ke={class:"table text-center"},xe=["checked"],_e=["value"],$e={key:0,class:"badge bg-success"},Pe={key:1,class:"badge bg-warning"},Se={class:"btn-group",role:"group"},Ce=["onClick","disabled"],Ue=["onClick"],Ve=["onClick"],Be={class:"d-flex justify-content-center mt-3"},Ne={class:"pagination"},ze={key:1,class:"page-link"},Ge={key:1,class:"page-link"},Oe={class:"modal-dialog"},je={class:"modal-content"},Te={class:"modal-header"},He={class:"modal-title"},De={class:"modal-body"},Me={class:"mb-3"},We={class:"form-label"},Le=["value"],Je={class:"mb-3"},Ie={class:"form-label"},qe={class:"row"},Ee={class:"col-md-6"},Re={class:"mb-3"},Qe={class:"form-label"},Ae={class:"col-md-6"},Fe={class:"mb-3"},Xe={class:"form-label"},Ke={class:"modal-footer"},Ye=["disabled"],Ze={key:0,class:"spinner-border spinner-border-sm me-2"},ts={class:"modal-dialog"},es={class:"modal-content"},ss={class:"modal-header"},os={class:"modal-title"},as={class:"modal-body"},ls={class:"mb-3"},ns={class:"form-label"},is=["value"],rs={class:"mb-3"},ds={class:"form-label"},cs=["value"],us={class:"mb-3"},ms={class:"form-label"},bs={class:"mb-3"},hs={class:"form-label"},ps={class:"row"},vs={class:"col-md-6"},gs={class:"mb-3"},fs={class:"form-label"},ys={class:"col-md-6"},ws={class:"mb-3"},ks={class:"form-label"},xs={class:"modal-footer"},_s={class:"modal-dialog modal-lg"},$s={class:"modal-content"},Ps={class:"modal-header"},Ss={class:"modal-title"},Cs={class:"modal-body"},Us={class:"mb-3"},Vs={class:"form-label"},Bs={class:"border p-2",style:{"max-height":"200px","overflow-y":"auto"}},Ns={class:"mb-3"},zs={class:"form-label"},Gs={class:"barcode-settings mt-3 p-3",style:{background:"#f8f9fa","border-radius":"8px",border:"1px solid #dee2e6"}},Os={class:"row mb-2"},js={class:"col-md-6"},Ts={class:"col-md-6"},Hs={class:"mb-3"},Ds={class:"btn-group btn-group-sm mt-1",role:"group"},Ms={class:"row"},Ws={class:"col-md-6"},Ls={class:"text-muted"},Js={class:"col-md-6"},Is={class:"text-muted"},qs={class:"row mt-3"},Es={class:"col-md-4"},Rs={class:"form-check"},Qs={class:"col-md-4"},As={class:"form-check"},Fs={class:"col-md-4"},Xs={class:"form-check"},Ks={key:0,class:"row mt-3"},Ys={class:"col-md-6"},Zs={class:"col-md-6"},to={class:"form-check mt-4"},eo={key:1,class:"row mt-2"},so={class:"col-md-6"},oo={class:"form-check"},ao={class:"alert alert-info mt-3 mb-0",style:{"font-size":"0.9em"}},lo={class:"mb-0 mt-2",style:{"font-size":"0.85em"}},no={class:"modal-footer"},io=["disabled"],ro={key:0,class:"spinner-border spinner-border-sm me-2"},co={class:"modal-dialog"},uo={class:"modal-content"},mo={class:"modal-header"},bo={class:"modal-title"},ho={class:"modal-body"},po={class:"alert alert-info"},vo={class:"mb-3"},go={class:"form-label"},fo={class:"row"},yo={class:"col-md-6"},wo={class:"mb-3"},ko={class:"form-label"},xo={class:"col-md-6"},_o={class:"mb-3"},$o={class:"form-label"},Po={class:"modal-footer"},So={class:"modal-dialog modal-lg"},Co={class:"modal-content"},Uo={class:"modal-header"},Vo={class:"modal-title"},Bo={class:"modal-body text-center"},No={key:0,class:"text-center"},zo={class:"spinner-border",role:"status"},Go={class:"visually-hidden"},Oo={key:1,class:"border p-4",style:{"max-width":"400px",margin:"0 auto"}},jo={class:"mb-3"},To={class:"fw-bold text-center"},Ho={class:"mb-3"},Do=["src","alt"],Mo={class:"mb-3"},Wo={class:"text-muted small"},Lo={class:"fw-bold font-monospace fs-5"},Jo={class:"modal-footer"},Io={__name:"Index",props:{products:Object,translations:Object,filters:Object},setup(l){var at;const h=rt(),_=l,p=f(!1),W=f(!1),G=J({search:((at=_.filters)==null?void 0:at.search)||""}),g=f([]),n=f(null),O=f(!1),j=f(!1),C=f(!1),w=f(!1),T=f(!1),x=J({type:"PNG",width:2,height:30}),d=J({type:"PNG",width:2,height:30}),s=J({type:"SVG",width:2,height:70,fontSize:10,margin:2,landscape:!0,highQuality:!0,pageWidth:38,pageHeight:26,copies:1,showBarcodeNumber:!0,showPrice:!1,price:0}),i=J({type:"PNG",width:2,height:30}),U=f(1),$=f(1),V=f(""),X=()=>{it.get(route("barcode.index"),G,{preserveState:!0,replace:!0})},K=()=>{g.value.length===_.products.data.length?g.value=[]:g.value=_.products.data.map(m=>m.id)},q=m=>{const e=_.products.data.find(k=>k.id===m);return e?e.name:""},E=async m=>{p.value=!0;try{const k=await(await fetch(route("barcode.generate"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:m.id,type:"PNG",width:2,height:30})})).json();k.success?(h.success("تم توليد الباركود بنجاح"),window.location.reload()):h.error(k.message||"حدث خطأ أثناء توليد الباركود")}catch(e){console.error("Generate barcode error:",e),h.error("حدث خطأ أثناء توليد الباركود")}finally{p.value=!1}},ot=async()=>{p.value=!0;try{const e=await(await fetch(route("barcode.generate"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:n.value.id,type:x.type,width:x.width,height:x.height})})).json();e.success?(h.success(e.message),O.value=!1,it.reload()):h.error(e.message)}catch{h.error("حدث خطأ أثناء توليد الباركود")}finally{p.value=!1}},ut=async()=>{p.value=!0;try{const e=await(await fetch(route("barcode.print"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:n.value.id,quantity:U.value,printer_settings:{type:d.type,width:d.width,height:d.height}})})).json();e.success?(h.success(e.message),j.value=!1):h.error(e.message)}catch{h.error("حدث خطأ أثناء الطباعة")}finally{p.value=!1}},mt=async()=>{p.value=!0;try{const e=await(await fetch(route("barcode.batch.print"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_ids:g.value,quantity_per_product:$.value,printer_settings:{type:s.type,width:s.width,height:s.height}})})).json();e.success?(h.success(e.message),await bt(e.results),C.value=!1,g.value=[]):h.error(e.message)}catch{h.error("حدث خطأ أثناء الطباعة المجمعة")}finally{p.value=!1}},bt=async m=>{try{const e=(await wt(async()=>{const{default:S}=await import("./BarcodePrinter.vue_vue_type_style_index_0_scoped_4fb4eefc_lang-4EuhrVZa.js").then(A=>A.J);return{default:S}},__vite__mapDeps([0,1,2,3]))).default,k=[];for(const S of m)if(S.success&&S.product){const A=document.createElementNS("http://www.w3.org/2000/svg","svg");e(A,S.product.barcode,{format:"CODE128",width:s.width,height:s.height,displayValue:!1,margin:s.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const gt=new XMLSerializer().serializeToString(A),ft="data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(gt)));k.push({...S,svgUrl:ft})}const B=window.open("","_blank","width=400,height=600");if(!B){h.error("فشل فتح نافذة الطباعة. يرجى السماح بالنوافذ المنبثقة.");return}let L="";for(const S of k)L+=`
        <div class="label-page">
          <div class="label-container">
            <div class="product-name">${S.product.name}</div>
            <img class="barcode-image" src="${S.svgUrl}" alt="Barcode">
            ${s.showBarcodeNumber?`<div class="barcode-text">${S.product.barcode}</div>`:""}
            ${s.showPrice&&s.price>0?`<div class="price-text">${s.price} دينار</div>`:""}
          </div>
          </div>
        `;const R=s.landscape?"landscape":"portrait",N=`${s.pageWidth}mm`,z=`${s.pageHeight}mm`,Q=s.landscape?`${N} ${z}`:`${z} ${N}`,o=s.landscape?"18mm":"22mm",D=`
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركودات</title>
      <style>
    /* Optimized for thermal printers - Batch Print with Dynamic Settings */
    @page { 
      size: ${Q}; 
      margin: 0; 
      orientation: ${R};
    }
    
        @media print {
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
      body {
        margin: 0;
        padding: 0;
      }
      .label-page {
        width: ${N};
        height: ${z};
        page-break-after: always;
      }
    }
    
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }
    
    .label-page {
      width: ${N};
      height: ${z};
      margin: 0;
      padding: 0;
      overflow: hidden;
      page-break-after: always;
    }
    
    .label-container {
      width: ${N};
      height: ${z};
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 1mm;
      box-sizing: border-box;
    }
    
    .product-name {
      width: 100%;
      font-size: ${s.fontSize}px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 1mm;
      line-height: 1.1;
      max-height: 8mm;
      overflow: hidden;
      word-wrap: break-word;
    }
    
    .barcode-image {
      width: auto;
      max-width: 90%;
      height: auto;
      max-height: ${o};
      margin: 1mm auto;
      display: block;
      object-fit: contain;
    }
    
    .barcode-text {
      width: 100%;
      font-size: ${Math.max(s.fontSize-2,4)}px;
      font-family: monospace;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
    }
    
    .price-text {
      width: 100%;
      font-size: ${Math.max(s.fontSize-1,5)}px;
      font-family: Arial, sans-serif;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
      color: #dc3545;
    }
    
    @media screen {
      body {
        padding: 20px;
        background: #f5f5f5;
      }
      .label-page {
        border: 1px solid #ccc;
        margin: 10px;
        background: white;
        display: inline-block;
      }
    }
  </style>
</head>
<body>
  ${L}
  
  <script>
    window.onload = function() {
      setTimeout(function() {
        window.print();
        setTimeout(function() {
          window.close();
        }, 2000);
      }, 1500);
    }
  <\/script>
</body>
</html>
    `;B.document.write(D),B.document.close()}catch(e){console.error("Batch print error:",e),h.error("حدث خطأ أثناء طباعة الباركودات")}},ht=async m=>{n.value=m,W.value=!0,T.value=!0;try{const e=new URLSearchParams({code:m.barcode,type:i.type,width:i.width,height:i.height}),k=await fetch(`${route("barcode.preview")}?${e}`);if(k.ok){const B=await k.blob();V.value=URL.createObjectURL(B)}else h.error("فشل في تحميل معاينة الباركود")}catch{h.error("حدث خطأ أثناء تحميل المعاينة")}finally{W.value=!1}},pt=m=>{const e=new URLSearchParams({type:i.type,width:i.width,height:i.height});window.open(`${route("barcode.download",m.id)}?${e}`,"_blank")},vt=()=>{localStorage.setItem("printerSettings",JSON.stringify(i)),h.success("تم حفظ إعدادات الطابعة"),w.value=!1};return dt(()=>{const m=localStorage.getItem("printerSettings");if(m){const e=JSON.parse(m);Object.assign(i,e),Object.assign(x,e),Object.assign(d,e),Object.assign(s,e)}}),(m,e)=>(c(),M(kt,{translations:l.translations},{default:F(()=>{var k,B,L,R,N,z,Q;return[t("div",Zt,[t("h1",te,a(l.translations.barcode_generation),1),t("nav",null,[t("ol",ee,[t("li",se,[yt(Y(Z),{class:"nav-link dark:text-white",href:m.route("dashboard")},{default:F(()=>[u(a(l.translations.Home),1)]),_:1},8,["href"])]),t("li",oe,a(l.translations.barcode_generation),1)])])]),t("section",ae,[t("div",le,[t("div",ne,[t("div",ie,[t("div",re,[t("form",{onSubmit:tt(X,["prevent"])},[t("div",de,[t("div",ce,[r(t("input",{type:"text",class:"form-control","onUpdate:modelValue":e[0]||(e[0]=o=>G.search=o),placeholder:l.translations.search},null,8,ue),[[v,G.search]])]),t("div",me,[t("button",be,[u(a(l.translations.search)+" ",1),e[40]||(e[40]=t("i",{class:"bi bi-search"},null,-1))])]),t("div",he,[t("button",{type:"button",class:"btn btn-success",onClick:e[1]||(e[1]=o=>C.value=!0),disabled:g.value.length===0},a(l.translations.batch_print)+" ("+a(g.value.length)+") ",9,pe)]),t("div",ve,[t("button",{type:"button",class:"btn btn-info",onClick:e[2]||(e[2]=o=>w.value=!0)},a(l.translations.printer_settings),1)])])],32)])])]),t("div",ge,[t("div",fe,[t("div",ye,[t("div",we,[t("table",ke,[t("thead",null,[t("tr",null,[t("th",null,[t("input",{type:"checkbox",onChange:K,checked:g.value.length===l.products.data.length&&l.products.data.length>0},null,40,xe)]),t("th",null,a(l.translations.name),1),t("th",null,a(l.translations.model),1),t("th",null,a(l.translations.barcode),1),t("th",null,a(l.translations.quantity),1),t("th",null,a(l.translations.price),1),t("th",null,a(l.translations.actions),1)])]),t("tbody",null,[(c(!0),b(nt,null,lt(l.products.data,o=>(c(),b("tr",{key:o.id},[t("td",null,[r(t("input",{type:"checkbox",value:o.id,"onUpdate:modelValue":e[3]||(e[3]=D=>g.value=D)},null,8,_e),[[P,g.value]])]),t("td",null,a(o.name),1),t("td",null,a(o.model),1),t("td",null,[o.barcode?(c(),b("span",$e,a(o.barcode),1)):(c(),b("span",Pe,a(l.translations.no_barcode),1))]),t("td",null,a(o.quantity),1),t("td",null,a(o.price)+" "+a(l.translations.dinar),1),t("td",null,[t("div",Se,[o.barcode?y("",!0):(c(),b("button",{key:0,class:"btn btn-sm btn-primary",onClick:D=>E(o),disabled:p.value},[e[41]||(e[41]=t("i",{class:"bi bi-qr-code"},null,-1)),u(" "+a(l.translations.generate),1)],8,Ce)),o.barcode?(c(),M(st,{key:1,"barcode-data":o.barcode,"product-name":o.name,translations:l.translations,"printer-settings":d},null,8,["barcode-data","product-name","translations","printer-settings"])):y("",!0),o.barcode?(c(),b("button",{key:2,class:"btn btn-sm btn-info",onClick:D=>ht(o)},[e[42]||(e[42]=t("i",{class:"bi bi-eye"},null,-1)),u(" "+a(l.translations.preview),1)],8,Ue)):y("",!0),o.barcode?(c(),b("button",{key:3,class:"btn btn-sm btn-secondary",onClick:D=>pt(o)},[e[43]||(e[43]=t("i",{class:"bi bi-download"},null,-1)),u(" "+a(l.translations.download),1)],8,Ve)):y("",!0)])])]))),128))])])]),t("div",Be,[t("nav",null,[t("ul",Ne,[t("li",{class:H(["page-item",{disabled:!l.products.prev_page_url}])},[l.products.prev_page_url?(c(),M(Y(Z),{key:0,class:"page-link",href:l.products.prev_page_url},{default:F(()=>[u(a(l.translations.previous),1)]),_:1},8,["href"])):(c(),b("span",ze,a(l.translations.previous),1))],2),t("li",{class:H(["page-item",{disabled:!l.products.next_page_url}])},[l.products.next_page_url?(c(),M(Y(Z),{key:0,class:"page-link",href:l.products.next_page_url},{default:F(()=>[u(a(l.translations.next),1)]),_:1},8,["href"])):(c(),b("span",Ge,a(l.translations.next),1))],2)])])])])])])]),t("div",{class:H(["modal fade",{show:O.value}]),style:I({display:O.value?"block":"none"})},[t("div",Oe,[t("div",je,[t("div",Te,[t("h5",He,a(l.translations.generate_barcode),1),t("button",{type:"button",class:"btn-close",onClick:e[4]||(e[4]=o=>O.value=!1)})]),t("div",De,[t("form",{onSubmit:tt(ot,["prevent"])},[t("div",Me,[t("label",We,a(l.translations.product_name),1),t("input",{type:"text",class:"form-control",value:(k=n.value)==null?void 0:k.name,readonly:""},null,8,Le)]),t("div",Je,[t("label",Ie,a(l.translations.barcode_type),1),r(t("select",{class:"form-select","onUpdate:modelValue":e[5]||(e[5]=o=>x.type=o)},e[44]||(e[44]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[et,x.type]])]),t("div",qe,[t("div",Ee,[t("div",Re,[t("label",Qe,a(l.translations.width),1),r(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[6]||(e[6]=o=>x.width=o),min:"1",max:"10"},null,512),[[v,x.width]])])]),t("div",Ae,[t("div",Fe,[t("label",Xe,a(l.translations.height),1),r(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[7]||(e[7]=o=>x.height=o),min:"10",max:"200"},null,512),[[v,x.height]])])])])],32)]),t("div",Ke,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[8]||(e[8]=o=>O.value=!1)},a(l.translations.cancel),1),t("button",{type:"button",class:"btn btn-primary",onClick:ot,disabled:p.value},[p.value?(c(),b("span",Ze)):y("",!0),u(" "+a(l.translations.generate),1)],8,Ye)])])])],6),t("div",{class:H(["modal fade",{show:j.value}]),style:I({display:j.value?"block":"none"})},[t("div",ts,[t("div",es,[t("div",ss,[t("h5",os,a(l.translations.print_barcode),1),t("button",{type:"button",class:"btn-close",onClick:e[9]||(e[9]=o=>j.value=!1)})]),t("div",as,[t("form",{onSubmit:tt(ut,["prevent"])},[t("div",ls,[t("label",ns,a(l.translations.product_name),1),t("input",{type:"text",class:"form-control",value:(B=n.value)==null?void 0:B.name,readonly:""},null,8,is)]),t("div",rs,[t("label",ds,a(l.translations.barcode),1),t("input",{type:"text",class:"form-control",value:(L=n.value)==null?void 0:L.barcode,readonly:""},null,8,cs)]),t("div",us,[t("label",ms,a(l.translations.print_quantity),1),r(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[10]||(e[10]=o=>U.value=o),min:"1",max:"100",value:"1"},null,512),[[v,U.value]])]),t("div",bs,[t("label",hs,a(l.translations.barcode_type),1),r(t("select",{class:"form-select","onUpdate:modelValue":e[11]||(e[11]=o=>d.type=o)},e[45]||(e[45]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[et,d.type]])]),t("div",ps,[t("div",vs,[t("div",gs,[t("label",fs,a(l.translations.width),1),r(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[12]||(e[12]=o=>d.width=o),min:"1",max:"10"},null,512),[[v,d.width]])])]),t("div",ys,[t("div",ws,[t("label",ks,a(l.translations.height),1),r(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[13]||(e[13]=o=>d.height=o),min:"10",max:"200"},null,512),[[v,d.height]])])])])],32)]),t("div",xs,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[14]||(e[14]=o=>j.value=!1)},a(l.translations.cancel),1),(R=n.value)!=null&&R.barcode?(c(),M(st,{key:0,"barcode-data":n.value.barcode,"product-name":n.value.name,translations:l.translations,"printer-settings":d},null,8,["barcode-data","product-name","translations","printer-settings"])):y("",!0)])])])],6),t("div",{class:H(["modal fade",{show:C.value}]),style:I({display:C.value?"block":"none"})},[t("div",_s,[t("div",$s,[t("div",Ps,[t("h5",Ss,a(l.translations.batch_print),1),t("button",{type:"button",class:"btn-close",onClick:e[15]||(e[15]=o=>C.value=!1)})]),t("div",Cs,[t("div",Us,[t("label",Vs,a(l.translations.selected_products)+" ("+a(g.value.length)+")",1),t("div",Bs,[(c(!0),b(nt,null,lt(g.value,o=>(c(),b("div",{key:o,class:"mb-1"},a(q(o)),1))),128))])]),t("div",Ns,[t("label",zs,a(l.translations.quantity_per_product),1),r(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[16]||(e[16]=o=>$.value=o),min:"1",max:"10",value:"1"},null,512),[[v,$.value]])]),t("div",Gs,[e[63]||(e[63]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-sliders"}),u(" إعدادات الباركود")],-1)),t("div",Os,[t("div",js,[e[46]||(e[46]=t("label",{class:"form-label"},[t("strong",null,"📏 عرض الورق (mm)")],-1)),r(t("input",{type:"number",class:"form-control form-control-sm",min:"20",max:"80",step:"1","onUpdate:modelValue":e[17]||(e[17]=o=>s.pageWidth=o)},null,512),[[v,s.pageWidth,void 0,{number:!0}]])]),t("div",Ts,[e[47]||(e[47]=t("label",{class:"form-label"},[t("strong",null,"📏 ارتفاع الورق (mm)")],-1)),r(t("input",{type:"number",class:"form-control form-control-sm",min:"20",max:"80",step:"1","onUpdate:modelValue":e[18]||(e[18]=o=>s.pageHeight=o)},null,512),[[v,s.pageHeight,void 0,{number:!0}]])])]),t("div",Hs,[e[49]||(e[49]=t("small",{class:"text-muted"},"أحجام شائعة:",-1)),t("div",Ds,[t("button",{type:"button",class:"btn btn-outline-primary",onClick:e[19]||(e[19]=o=>{s.pageWidth=38,s.pageHeight=26})},e[48]||(e[48]=[t("strong",null,"38×26",-1)])),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[20]||(e[20]=o=>{s.pageWidth=35,s.pageHeight=28})}," 35×28 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[21]||(e[21]=o=>{s.pageWidth=38,s.pageHeight=28})}," 38×28 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[22]||(e[22]=o=>{s.pageWidth=40,s.pageHeight=30})}," 40×30 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[23]||(e[23]=o=>{s.pageWidth=50,s.pageHeight=30})}," 50×30 ")])]),e[64]||(e[64]=t("hr",null,null,-1)),t("div",Ms,[t("div",Ws,[e[50]||(e[50]=t("label",{class:"form-label"},"ارتفاع الباركود",-1)),r(t("input",{type:"range",class:"form-range",min:"30",max:"150",step:"5","onUpdate:modelValue":e[24]||(e[24]=o=>s.height=o)},null,512),[[v,s.height]]),t("small",Ls,a(s.height)+"px",1)]),t("div",Js,[e[51]||(e[51]=t("label",{class:"form-label"},"حجم الخط",-1)),r(t("input",{type:"range",class:"form-range",min:"6",max:"20",step:"1","onUpdate:modelValue":e[25]||(e[25]=o=>s.fontSize=o)},null,512),[[v,s.fontSize]]),t("small",Is,a(s.fontSize)+"px",1)])]),t("div",qs,[t("div",Es,[t("div",Rs,[r(t("input",{class:"form-check-input",type:"checkbox",id:"batchLandscapeMode","onUpdate:modelValue":e[26]||(e[26]=o=>s.landscape=o)},null,512),[[P,s.landscape]]),e[52]||(e[52]=t("label",{class:"form-check-label",for:"batchLandscapeMode"}," طباعة بالعرض (Landscape) ",-1))])]),t("div",Qs,[t("div",As,[r(t("input",{class:"form-check-input",type:"checkbox",id:"batchHighQuality","onUpdate:modelValue":e[27]||(e[27]=o=>s.highQuality=o)},null,512),[[P,s.highQuality]]),e[53]||(e[53]=t("label",{class:"form-check-label",for:"batchHighQuality"}," جودة عالية (SVG) ",-1))])]),t("div",Fs,[t("div",Xs,[r(t("input",{class:"form-check-input",type:"checkbox",id:"showBarcodeNumber","onUpdate:modelValue":e[28]||(e[28]=o=>s.showBarcodeNumber=o)},null,512),[[P,s.showBarcodeNumber]]),e[54]||(e[54]=t("label",{class:"form-check-label",for:"showBarcodeNumber"}," عرض رقم الباركود ",-1))])])]),s.showPrice?(c(),b("div",Ks,[t("div",Ys,[e[55]||(e[55]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-currency-dollar"}),u(" سعر البيع")])],-1)),r(t("input",{type:"number",class:"form-control",min:"0",step:"0.01","onUpdate:modelValue":e[29]||(e[29]=o=>s.price=o),placeholder:"أدخل سعر البيع"},null,512),[[v,s.price,void 0,{number:!0}]])]),t("div",Zs,[t("div",to,[r(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":e[30]||(e[30]=o=>s.showPrice=o)},null,512),[[P,s.showPrice]]),e[56]||(e[56]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])):y("",!0),s.showPrice?y("",!0):(c(),b("div",eo,[t("div",so,[t("div",oo,[r(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":e[31]||(e[31]=o=>s.showPrice=o)},null,512),[[P,s.showPrice]]),e[57]||(e[57]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])),t("div",ao,[e[62]||(e[62]=t("strong",null,[t("i",{class:"bi bi-info-circle"}),u(" للطابعة الحرارية MHT-L58G:")],-1)),t("ul",lo,[t("li",null,[e[58]||(e[58]=t("strong",null,"حجم الورق المحدد:",-1)),u(" "+a(s.pageWidth)+"mm × "+a(s.pageHeight)+"mm",1)]),t("li",null,[e[59]||(e[59]=t("strong",null,"اتجاه:",-1)),u(" "+a(s.landscape?"أفقي (Landscape)":"عمودي (Portrait)"),1)]),t("li",null,[e[60]||(e[60]=t("strong",null,"حجم الطباعة:",-1)),u(" "+a(s.landscape?`${s.pageWidth}mm × ${s.pageHeight}mm`:`${s.pageHeight}mm × ${s.pageWidth}mm`),1)]),e[61]||(e[61]=t("li",null,"⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة",-1))])])])]),t("div",no,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[32]||(e[32]=o=>C.value=!1)},a(l.translations.cancel),1),t("button",{type:"button",class:"btn btn-success",onClick:mt,disabled:p.value},[p.value?(c(),b("span",ro)):y("",!0),e[65]||(e[65]=t("i",{class:"bi bi-printer"},null,-1)),u(" "+a(l.translations.print_all)+" ("+a(g.value.length*$.value)+") ",1)],8,io)])])])],6),t("div",{class:H(["modal fade",{show:w.value}]),style:I({display:w.value?"block":"none"})},[t("div",co,[t("div",uo,[t("div",mo,[t("h5",bo,a(l.translations.printer_settings),1),t("button",{type:"button",class:"btn-close",onClick:e[33]||(e[33]=o=>w.value=!1)})]),t("div",ho,[t("div",po,[e[66]||(e[66]=t("i",{class:"bi bi-info-circle"},null,-1)),u(" "+a(l.translations.printer_settings_info),1)]),t("div",vo,[t("label",go,a(l.translations.default_barcode_type),1),r(t("select",{class:"form-select","onUpdate:modelValue":e[34]||(e[34]=o=>i.type=o)},e[67]||(e[67]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[et,i.type]])]),t("div",fo,[t("div",yo,[t("div",wo,[t("label",ko,a(l.translations.default_width),1),r(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[35]||(e[35]=o=>i.width=o),min:"1",max:"10"},null,512),[[v,i.width]])])]),t("div",xo,[t("div",_o,[t("label",$o,a(l.translations.default_height),1),r(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[36]||(e[36]=o=>i.height=o),min:"10",max:"200"},null,512),[[v,i.height]])])])])]),t("div",Po,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[37]||(e[37]=o=>w.value=!1)},a(l.translations.cancel),1),t("button",{type:"button",class:"btn btn-primary",onClick:vt},a(l.translations.save),1)])])])],6),t("div",{class:H(["modal fade",{show:T.value}]),style:I({display:T.value?"block":"none"})},[t("div",So,[t("div",Co,[t("div",Uo,[t("h5",Vo,a(l.translations.barcode_preview),1),t("button",{type:"button",class:"btn-close",onClick:e[38]||(e[38]=o=>T.value=!1)})]),t("div",Bo,[W.value?(c(),b("div",No,[t("div",zo,[t("span",Go,a(l.translations.loading),1)])])):V.value?(c(),b("div",Oo,[t("div",jo,[t("h4",To,a(((N=n.value)==null?void 0:N.name)||"Product"),1)]),t("div",Ho,[t("img",{src:V.value,alt:l.translations.barcode_preview,class:"img-fluid",style:{width:"100%",padding:"10px",background:"white"}},null,8,Do)]),t("div",Mo,[t("div",Wo,a(l.translations.barcode)+":",1),t("div",Lo,a((z=n.value)==null?void 0:z.barcode),1)]),e[68]||(e[68]=t("div",{class:"alert alert-info small"},[t("i",{class:"bi bi-info-circle"}),u(" هذه معاينة لما سيتم طباعته ")],-1))])):y("",!0)]),t("div",Jo,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[39]||(e[39]=o=>T.value=!1)},a(l.translations.close),1),(Q=n.value)!=null&&Q.barcode?(c(),M(st,{key:0,"barcode-data":n.value.barcode,"product-name":n.value.name,translations:l.translations,"printer-settings":d},null,8,["barcode-data","product-name","translations","printer-settings"])):y("",!0)])])])],6)])]}),_:1},8,["translations"]))}},Ao=ct(Io,[["__scopeId","data-v-c0ee5edc"]]);export{Ao as default};
