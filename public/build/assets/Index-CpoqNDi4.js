const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/BarcodePrinter.vue_vue_type_style_index_0_scoped_d5167dbe_lang-HxCk5pMT.js","assets/app-D346Pjzq.js","assets/app-DPSgePyu.css","assets/BarcodePrinter-Cd_iuy5J.css"])))=>i.map(i=>d[i]);
import{s as ut,r as w,o as mt,a as r,c as u,b as t,e as p,d as m,t as o,l as d,x as y,v as S,y as _,z as E,h as M,w as X,i as $t,u as Z,p as tt,j as et,f as it,F as rt,n as G,A as st,g as dt,_ as ct}from"./app-D346Pjzq.js";import{_ as Pt}from"./AuthenticatedLayout-BheGbMS3.js";import{a as St}from"./BarcodePrinter.vue_vue_type_style_index_0_scoped_d5167dbe_lang-HxCk5pMT.js";import{_ as bt}from"./_plugin-vue_export-helper-DlAUqK2U.js";const Ct={class:"barcode-printer"},Bt=["disabled"],zt={key:0,class:"spinner-border spinner-border-sm me-1"},Ut={class:"barcode-settings mt-2"},Vt={class:"mb-3"},Nt={class:"text-muted"},Ht={class:"row"},Tt={class:"col-md-6"},Wt={class:"text-muted"},Ot={class:"col-md-6"},jt={class:"text-muted"},Gt={class:"row mt-2"},Dt={class:"col-md-3"},Mt={class:"form-check"},It={class:"col-md-3"},Lt={class:"form-check"},Jt={class:"col-md-3"},At={class:"form-check"},Et={key:0,class:"row mt-3"},Rt={class:"col-md-6"},qt={class:"col-md-6"},Ft={class:"form-check mt-4"},Qt={key:1,class:"row mt-2"},Xt={class:"col-md-6"},Kt={class:"form-check"},Yt={key:2,class:"alert alert-info mt-3"},Zt={class:"mb-0"},te={key:0,class:"barcode-preview mt-3 p-3 text-center",style:{background:"white",border:"2px dashed #dee2e6","border-radius":"8px"}},ee=["src"],se={class:"text-muted mt-2 d-block"},oe={__name:"BarcodePrinter",props:{barcodeData:{type:String,required:!0},productName:{type:String,default:""},translations:{type:Object,required:!0},printerSettings:{type:Object,default:()=>({width:2,height:30,type:"PNG"})}},setup(n){const v=ut(),P=n,f=w(!1),I=w(null),T=w(""),h=w(!1),i=w({width:2,height:70,fontSize:10,margin:2,landscape:!0,highQuality:!0,pageWidth:38,pageHeight:26,copies:1,showBarcodeNumber:!0,showPrice:!1,price:0}),L=()=>{h.value=!h.value},W=async()=>{if(!P.barcodeData){v.error("لا يوجد بيانات باركود للطباعة");return}f.value=!0;try{const b=U();await V(b)}catch(b){console.error("Print error:",b),v.error("حدث خطأ أثناء الطباعة")}finally{f.value=!1}},U=()=>{try{const b=document.createElementNS("http://www.w3.org/2000/svg","svg");St(b,P.barcodeData,{format:"CODE128",width:i.value.width,height:i.value.height,displayValue:!1,margin:i.value.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const a=new XMLSerializer().serializeToString(b),s=new Blob([a],{type:"image/svg+xml;charset=utf-8"}),g=URL.createObjectURL(s);return"data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(a)))}catch(b){throw console.error("JsBarcode generation error:",b),b}},$=()=>{if(P.barcodeData)try{T.value=U()}catch(b){console.error("Preview update error:",b)}},V=async b=>{try{const a=window.open("","_blank","width=300,height=400"),s=P.productName||"Product",g=P.barcodeData,C=O(b,s,g);a.document.write(C),a.document.close(),v.success("تم إرسال الباركود للطباعة")}catch(a){throw console.error("Direct print error:",a),a}},O=(b,a="Product",s="")=>{const g=i.value.landscape?"landscape":"portrait",C=`${i.value.pageWidth}mm`,z=`${i.value.pageHeight}mm`,J=i.value.landscape?`${C} ${z}`:`${z} ${C}`,K=i.value.landscape?"18mm":"22mm";let R="";for(let A=0;A<i.value.copies;A++)R+=`
  <div class="label-page">
    <div class="label-container">
      <div class="product-name">${a}</div>
      <img class="barcode-image" src="${b}" alt="Barcode">
      ${i.value.showBarcodeNumber?`<div class="barcode-text">${s}</div>`:""}
      ${i.value.showPrice&&i.value.price>0?`<div class="price-text">${i.value.price} ريال</div>`:""}
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
      size: ${J}; 
      margin: 0; 
      orientation: ${g};
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
      width: ${C};
      height: ${z};
      margin: 0;
      padding: 0;
      overflow: hidden;
      page-break-after: always;
    }
    .label-container {
      width: ${C};
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
      font-size: ${i.value.fontSize}px;
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
      font-size: ${Math.max(i.value.fontSize-2,4)}px;
      font-family: monospace;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
    }
    .price-text {
      width: 100%;
      font-size: ${Math.max(i.value.fontSize-1,5)}px;
      font-family: Arial, sans-serif;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
      color: #dc3545;
    }
  </style>
</head>
<body>
  ${R}
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
</html>`};return mt(()=>{P.barcodeData&&$()}),(b,a)=>(r(),u("div",Ct,[t("button",{class:"btn btn-sm btn-success me-2",onClick:W,disabled:f.value||!n.barcodeData},[f.value?(r(),u("span",zt)):p("",!0),a[9]||(a[9]=t("i",{class:"bi bi-printer"},null,-1)),m(" "+o(n.translations.print),1)],8,Bt),t("div",Ut,[t("div",Vt,[a[10]||(a[10]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-files"}),m(" عدد النسخ")])],-1)),d(t("input",{type:"number",class:"form-control",min:"1",max:"100","onUpdate:modelValue":a[0]||(a[0]=s=>i.value.copies=s),placeholder:"أدخل عدد النسخ المطلوبة"},null,512),[[y,i.value.copies,void 0,{number:!0}]]),t("small",Nt,"سيتم طباعة "+o(i.value.copies)+" نسخة من الباركود",1)]),a[27]||(a[27]=t("hr",null,null,-1)),t("div",Ht,[t("div",Tt,[a[11]||(a[11]=t("label",{class:"form-label"},"ارتفاع الباركود",-1)),d(t("input",{type:"range",class:"form-range",min:"30",max:"150",step:"5","onUpdate:modelValue":a[1]||(a[1]=s=>i.value.height=s),onInput:$},null,544),[[y,i.value.height]]),t("small",Wt,o(i.value.height)+"px",1)]),t("div",Ot,[a[12]||(a[12]=t("label",{class:"form-label"},"حجم الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"6",max:"20",step:"1","onUpdate:modelValue":a[2]||(a[2]=s=>i.value.fontSize=s),onInput:$},null,544),[[y,i.value.fontSize]]),t("small",jt,o(i.value.fontSize)+"px",1)])]),t("div",Gt,[t("div",Dt,[t("div",Mt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"landscapeMode","onUpdate:modelValue":a[3]||(a[3]=s=>i.value.landscape=s),onChange:$},null,544),[[S,i.value.landscape]]),a[13]||(a[13]=t("label",{class:"form-check-label",for:"landscapeMode"}," طباعة بالعرض (Landscape) ",-1))])]),t("div",It,[t("div",Lt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"highQuality","onUpdate:modelValue":a[4]||(a[4]=s=>i.value.highQuality=s),onChange:$},null,544),[[S,i.value.highQuality]]),a[14]||(a[14]=t("label",{class:"form-check-label",for:"highQuality"}," جودة عالية ",-1))])]),t("div",Jt,[t("div",At,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showBarcodeNumber","onUpdate:modelValue":a[5]||(a[5]=s=>i.value.showBarcodeNumber=s)},null,512),[[S,i.value.showBarcodeNumber]]),a[15]||(a[15]=t("label",{class:"form-check-label",for:"showBarcodeNumber"}," عرض رقم الباركود ",-1))])]),t("div",{class:"col-md-3"},[t("button",{class:"btn btn-sm btn-info",onClick:L},a[16]||(a[16]=[t("i",{class:"bi bi-info-circle"},null,-1),m(" معلومات الطابعة ")]))])]),i.value.showPrice?(r(),u("div",Et,[t("div",Rt,[a[17]||(a[17]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-currency-dollar"}),m(" سعر البيع")])],-1)),d(t("input",{type:"number",class:"form-control",min:"0",step:"0.01","onUpdate:modelValue":a[6]||(a[6]=s=>i.value.price=s),placeholder:"أدخل سعر البيع",onInput:$},null,544),[[y,i.value.price,void 0,{number:!0}]])]),t("div",qt,[t("div",Ft,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":a[7]||(a[7]=s=>i.value.showPrice=s)},null,512),[[S,i.value.showPrice]]),a[18]||(a[18]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])):p("",!0),i.value.showPrice?p("",!0):(r(),u("div",Qt,[t("div",Xt,[t("div",Kt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":a[8]||(a[8]=s=>i.value.showPrice=s)},null,512),[[S,i.value.showPrice]]),a[19]||(a[19]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])),h.value?(r(),u("div",Yt,[a[26]||(a[26]=t("h6",null,[t("i",{class:"bi bi-printer"}),m(" معلومات الطابعة الحرارية:")],-1)),t("ul",Zt,[a[23]||(a[23]=t("li",null,[t("strong",null,"الطابعة:"),m(" Thermal Printer (MHT-L58G)")],-1)),t("li",null,[a[20]||(a[20]=t("strong",null,"حجم الورق المحدد:",-1)),m(" "+o(i.value.pageWidth)+"mm × "+o(i.value.pageHeight)+"mm",1)]),t("li",null,[a[21]||(a[21]=t("strong",null,"الاتجاه:",-1)),m(" "+o(i.value.landscape?"أفقي (Landscape)":"عمودي (Portrait)"),1)]),t("li",null,[a[22]||(a[22]=t("strong",null,"حجم الطباعة:",-1)),m(" "+o(i.value.landscape?`${i.value.pageWidth}mm × ${i.value.pageHeight}mm`:`${i.value.pageHeight}mm × ${i.value.pageWidth}mm`),1)]),a[24]||(a[24]=t("li",null,[t("strong",null,"دقة الطباعة الموصى بها:"),m(" 203 DPI")],-1)),a[25]||(a[25]=t("li",null,"⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة",-1))])])):p("",!0)]),T.value?(r(),u("div",te,[a[28]||(a[28]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-eye"}),m(" معاينة الباركود")],-1)),t("div",{class:"preview-container",style:_({width:`${i.value.pageWidth*3.78}px`,height:`${i.value.pageHeight*3.78}px`,margin:"0 auto",border:"1px solid #ccc",display:"flex",flexDirection:"column",justifyContent:"center",alignItems:"center",padding:"8px",background:"#fff"})},[t("div",{class:"product-name",style:_({fontSize:`${i.value.fontSize}px`,fontWeight:"bold",textAlign:"center",marginBottom:"4px",maxHeight:"30px",overflow:"hidden",wordWrap:"break-word",width:"100%"})},o(n.productName),5),t("img",{src:T.value,alt:"Barcode Preview",style:_({maxWidth:"90%",height:"auto",maxHeight:i.value.landscape?"68px":"83px",margin:"4px auto"})},null,12,ee),i.value.showBarcodeNumber?(r(),u("div",{key:0,class:"barcode-text",style:_({fontSize:`${Math.max(i.value.fontSize-2,4)}px`,fontFamily:"monospace",textAlign:"center",marginTop:"4px",fontWeight:"bold",width:"100%"})},o(n.barcodeData),5)):p("",!0),i.value.showPrice&&i.value.price>0?(r(),u("div",{key:1,class:"price-text",style:_({fontSize:`${Math.max(i.value.fontSize-1,5)}px`,textAlign:"center",marginTop:"4px",fontWeight:"bold",color:"#dc3545",width:"100%"})},o(i.value.price)+" دينار ",5)):p("",!0)],4),t("small",se,"الحجم الفعلي: "+o(i.value.pageWidth)+"mm × "+o(i.value.pageHeight)+"mm",1)])):p("",!0),t("canvas",{ref_key:"barcodeCanvas",ref:I,style:{display:"none"}},null,512)]))}},ot=bt(oe,[["__scopeId","data-v-d5167dbe"]]),ae={class:"pagetitle dark:text-white"},le={class:"dark:text-white"},ne={class:"breadcrumb"},ie={class:"breadcrumb-item"},re={class:"breadcrumb-item active dark:text-white"},de={class:"section dashboard"},ce={class:"row"},ue={class:"col-12"},me={class:"card"},be={class:"card-body"},he={class:"row"},pe={class:"col-md-4"},ve=["placeholder"],ge={class:"col-md-2"},fe={type:"submit",class:"btn btn-primary"},ye={class:"col-md-3"},we=["disabled"],xe={class:"col-md-3"},ke={class:"col-12"},_e={class:"card"},$e={class:"card-body"},Pe={class:"table-responsive"},Se={class:"table text-center"},Ce=["checked"],Be=["value"],ze={key:0,class:"badge bg-success"},Ue={key:1,class:"badge bg-warning"},Ve={class:"btn-group",role:"group"},Ne=["onClick","disabled"],He=["onClick"],Te=["onClick"],We={class:"d-flex justify-content-center mt-3"},Oe={class:"pagination"},je={key:1,class:"page-link"},Ge={key:1,class:"page-link"},De={class:"modal-dialog"},Me={class:"modal-content"},Ie={class:"modal-header"},Le={class:"modal-title"},Je={class:"modal-body"},Ae={class:"mb-3"},Ee={class:"form-label"},Re=["value"],qe={class:"mb-3"},Fe={class:"form-label"},Qe={class:"row"},Xe={class:"col-md-6"},Ke={class:"mb-3"},Ye={class:"form-label"},Ze={class:"col-md-6"},ts={class:"mb-3"},es={class:"form-label"},ss={class:"modal-footer"},os=["disabled"],as={key:0,class:"spinner-border spinner-border-sm me-2"},ls={class:"modal-dialog"},ns={class:"modal-content"},is={class:"modal-header"},rs={class:"modal-title"},ds={class:"modal-body"},cs={class:"mb-3"},us={class:"form-label"},ms=["value"],bs={class:"mb-3"},hs={class:"form-label"},ps=["value"],vs={class:"mb-3"},gs={class:"form-label"},fs={class:"mb-3"},ys={class:"form-label"},ws={class:"row"},xs={class:"col-md-6"},ks={class:"mb-3"},_s={class:"form-label"},$s={class:"col-md-6"},Ps={class:"mb-3"},Ss={class:"form-label"},Cs={class:"modal-footer"},Bs={class:"modal-dialog modal-lg"},zs={class:"modal-content"},Us={class:"modal-header"},Vs={class:"modal-title"},Ns={class:"modal-body"},Hs={class:"mb-3"},Ts={class:"form-label"},Ws={class:"border p-2",style:{"max-height":"200px","overflow-y":"auto"}},Os={class:"mb-3"},js={class:"form-label"},Gs={key:0,class:"barcode-preview mb-3 p-3 text-center",style:{background:"white",border:"2px dashed #dee2e6","border-radius":"8px"}},Ds=["src"],Ms={class:"text-muted mt-2 d-block"},Is={class:"barcode-settings mt-3 p-3",style:{background:"#f8f9fa","border-radius":"8px",border:"1px solid #dee2e6"}},Ls={class:"row mb-2"},Js={class:"col-md-6"},As={class:"col-md-6"},Es={class:"mb-3"},Rs={class:"btn-group btn-group-sm mt-1",role:"group"},qs={class:"row"},Fs={class:"col-md-6"},Qs={class:"text-muted"},Xs={class:"col-md-6"},Ks={class:"text-muted"},Ys={class:"row mt-3"},Zs={class:"col-md-4"},to={class:"form-check"},eo={class:"col-md-4"},so={class:"form-check"},oo={class:"col-md-4"},ao={class:"form-check"},lo={key:0,class:"row mt-3"},no={class:"col-md-6"},io={class:"col-md-6"},ro={class:"form-check mt-4"},co={key:1,class:"row mt-2"},uo={class:"col-md-6"},mo={class:"form-check"},bo={class:"alert alert-info mt-3 mb-0",style:{"font-size":"0.9em"}},ho={class:"mb-0 mt-2",style:{"font-size":"0.85em"}},po={class:"modal-footer"},vo=["disabled"],go={key:0,class:"spinner-border spinner-border-sm me-2"},fo={class:"modal-dialog"},yo={class:"modal-content"},wo={class:"modal-header"},xo={class:"modal-title"},ko={class:"modal-body"},_o={class:"alert alert-info"},$o={class:"mb-3"},Po={class:"form-label"},So={class:"row"},Co={class:"col-md-6"},Bo={class:"mb-3"},zo={class:"form-label"},Uo={class:"col-md-6"},Vo={class:"mb-3"},No={class:"form-label"},Ho={class:"modal-footer"},To={class:"modal-dialog modal-lg"},Wo={class:"modal-content"},Oo={class:"modal-header"},jo={class:"modal-title"},Go={class:"modal-body text-center"},Do={key:0,class:"text-center"},Mo={class:"spinner-border",role:"status"},Io={class:"visually-hidden"},Lo={key:1,class:"border p-4",style:{"max-width":"400px",margin:"0 auto"}},Jo={class:"mb-3"},Ao={class:"fw-bold text-center"},Eo={class:"mb-3"},Ro=["src","alt"],qo={class:"mb-3"},Fo={class:"text-muted small"},Qo={class:"fw-bold font-monospace fs-5"},Xo={class:"modal-footer"},Ko={__name:"Index",props:{products:Object,translations:Object,filters:Object},setup(n){var nt;const v=ut(),P=n,f=w(!1),I=w(!1),T=E({search:((nt=P.filters)==null?void 0:nt.search)||""}),h=w([]),i=w(null),L=w(""),W=w(!1),U=w(!1),$=w(!1),V=w(!1),O=w(!1),b=E({type:"PNG",width:2,height:30}),a=E({type:"PNG",width:2,height:30}),s=E({type:"SVG",width:2,height:70,fontSize:10,margin:2,landscape:!0,highQuality:!0,pageWidth:38,pageHeight:26,copies:1,showBarcodeNumber:!0,showPrice:!1,price:0}),g=E({type:"PNG",width:2,height:30}),C=w(1),z=w(1),J=w(""),K=()=>{dt.get(route("barcode.index"),T,{preserveState:!0,replace:!0})},R=()=>{h.value.length===P.products.data.length?h.value=[]:h.value=P.products.data.map(c=>c.id)},A=c=>{const e=P.products.data.find(x=>x.id===c);return e?e.name:""},at=c=>P.products.data.find(e=>e.id===c),ht=()=>{$.value=!0,Y()},Y=async()=>{if(h.value.length===0)return;const c=at(h.value[0]);if(!(!c||!c.barcode))try{const e=(await ct(async()=>{const{default:j}=await import("./BarcodePrinter.vue_vue_type_style_index_0_scoped_d5167dbe_lang-HxCk5pMT.js").then(D=>D.J);return{default:j}},__vite__mapDeps([0,1,2,3]))).default,x=document.createElementNS("http://www.w3.org/2000/svg","svg");e(x,c.barcode,{format:"CODE128",width:s.width,height:s.height,displayValue:!1,margin:s.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const B=new XMLSerializer().serializeToString(x);L.value="data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(B)))}catch(e){console.error("Batch preview generation error:",e)}},pt=async c=>{f.value=!0;try{const x=await(await fetch(route("barcode.generate"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:c.id,type:"PNG",width:2,height:30})})).json();x.success?(v.success("تم توليد الباركود بنجاح"),window.location.reload()):v.error(x.message||"حدث خطأ أثناء توليد الباركود")}catch(e){console.error("Generate barcode error:",e),v.error("حدث خطأ أثناء توليد الباركود")}finally{f.value=!1}},lt=async()=>{f.value=!0;try{const e=await(await fetch(route("barcode.generate"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:i.value.id,type:b.type,width:b.width,height:b.height})})).json();e.success?(v.success(e.message),W.value=!1,dt.reload()):v.error(e.message)}catch{v.error("حدث خطأ أثناء توليد الباركود")}finally{f.value=!1}},vt=async()=>{f.value=!0;try{const e=await(await fetch(route("barcode.print"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:i.value.id,quantity:C.value,printer_settings:{type:a.type,width:a.width,height:a.height}})})).json();e.success?(v.success(e.message),U.value=!1):v.error(e.message)}catch{v.error("حدث خطأ أثناء الطباعة")}finally{f.value=!1}},gt=async()=>{f.value=!0;try{const e=await(await fetch(route("barcode.batch.print"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_ids:h.value,quantity_per_product:z.value,printer_settings:{type:s.type,width:s.width,height:s.height}})})).json();e.success?(v.success(e.message),await ft(e.results),$.value=!1,h.value=[]):v.error(e.message)}catch{v.error("حدث خطأ أثناء الطباعة المجمعة")}finally{f.value=!1}},ft=async c=>{try{const e=(await ct(async()=>{const{default:k}=await import("./BarcodePrinter.vue_vue_type_style_index_0_scoped_d5167dbe_lang-HxCk5pMT.js").then(Q=>Q.J);return{default:k}},__vite__mapDeps([0,1,2,3]))).default,x=[];for(const k of c)if(k.success&&k.product){const Q=document.createElementNS("http://www.w3.org/2000/svg","svg");e(Q,k.product.barcode,{format:"CODE128",width:s.width,height:s.height,displayValue:!1,margin:s.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const kt=new XMLSerializer().serializeToString(Q),_t="data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(kt)));x.push({...k,svgUrl:_t})}const B=window.open("","_blank","width=400,height=600");if(!B){v.error("فشل فتح نافذة الطباعة. يرجى السماح بالنوافذ المنبثقة.");return}let j="";for(const k of x)j+=`
        <div class="label-page">
          <div class="label-container">
            <div class="product-name">${k.product.name}</div>
            <img class="barcode-image" src="${k.svgUrl}" alt="Barcode">
            ${s.showBarcodeNumber?`<div class="barcode-text">${k.product.barcode}</div>`:""}
            ${s.showPrice&&s.price>0?`<div class="price-text">${s.price} دينار</div>`:""}
          </div>
          </div>
        `;const D=s.landscape?"landscape":"portrait",N=`${s.pageWidth}mm`,H=`${s.pageHeight}mm`,q=s.landscape?`${N} ${H}`:`${H} ${N}`,F=s.landscape?"18mm":"22mm",l=`
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركودات</title>
      <style>
    /* Optimized for thermal printers - Batch Print with Dynamic Settings */
    @page { 
      size: ${q}; 
      margin: 0; 
      orientation: ${D};
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
        height: ${H};
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
      height: ${H};
      margin: 0;
      padding: 0;
      overflow: hidden;
      page-break-after: always;
    }
    
    .label-container {
      width: ${N};
      height: ${H};
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
      max-height: ${F};
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
  ${j}
  
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
    `;B.document.write(l),B.document.close()}catch(e){console.error("Batch print error:",e),v.error("حدث خطأ أثناء طباعة الباركودات")}},yt=async c=>{i.value=c,I.value=!0,O.value=!0;try{const e=new URLSearchParams({code:c.barcode,type:g.type,width:g.width,height:g.height}),x=await fetch(`${route("barcode.preview")}?${e}`);if(x.ok){const B=await x.blob();J.value=URL.createObjectURL(B)}else v.error("فشل في تحميل معاينة الباركود")}catch{v.error("حدث خطأ أثناء تحميل المعاينة")}finally{I.value=!1}},wt=c=>{const e=new URLSearchParams({type:g.type,width:g.width,height:g.height});window.open(`${route("barcode.download",c.id)}?${e}`,"_blank")},xt=()=>{localStorage.setItem("printerSettings",JSON.stringify(g)),v.success("تم حفظ إعدادات الطابعة"),V.value=!1};return mt(()=>{const c=localStorage.getItem("printerSettings");if(c){const e=JSON.parse(c);Object.assign(g,e),Object.assign(b,e),Object.assign(a,e),Object.assign(s,e)}}),(c,e)=>(r(),M(Pt,{translations:n.translations},{default:X(()=>{var x,B,j,D,N,H,q,F;return[t("div",ae,[t("h1",le,o(n.translations.barcode_generation),1),t("nav",null,[t("ol",ne,[t("li",ie,[$t(Z(tt),{class:"nav-link dark:text-white",href:c.route("dashboard")},{default:X(()=>[m(o(n.translations.Home),1)]),_:1},8,["href"])]),t("li",re,o(n.translations.barcode_generation),1)])])]),t("section",de,[t("div",ce,[t("div",ue,[t("div",me,[t("div",be,[t("form",{onSubmit:et(K,["prevent"])},[t("div",he,[t("div",pe,[d(t("input",{type:"text",class:"form-control","onUpdate:modelValue":e[0]||(e[0]=l=>T.search=l),placeholder:n.translations.search},null,8,ve),[[y,T.search]])]),t("div",ge,[t("button",fe,[m(o(n.translations.search)+" ",1),e[39]||(e[39]=t("i",{class:"bi bi-search"},null,-1))])]),t("div",ye,[t("button",{type:"button",class:"btn btn-success",onClick:ht,disabled:h.value.length===0},o(n.translations.batch_print)+" ("+o(h.value.length)+") ",9,we)]),t("div",xe,[t("button",{type:"button",class:"btn btn-info",onClick:e[1]||(e[1]=l=>V.value=!0)},o(n.translations.printer_settings),1)])])],32)])])]),t("div",ke,[t("div",_e,[t("div",$e,[t("div",Pe,[t("table",Se,[t("thead",null,[t("tr",null,[t("th",null,[t("input",{type:"checkbox",onChange:R,checked:h.value.length===n.products.data.length&&n.products.data.length>0},null,40,Ce)]),t("th",null,o(n.translations.name),1),t("th",null,o(n.translations.model),1),t("th",null,o(n.translations.barcode),1),t("th",null,o(n.translations.quantity),1),t("th",null,o(n.translations.price),1),t("th",null,o(n.translations.actions),1)])]),t("tbody",null,[(r(!0),u(rt,null,it(n.products.data,l=>(r(),u("tr",{key:l.id},[t("td",null,[d(t("input",{type:"checkbox",value:l.id,"onUpdate:modelValue":e[2]||(e[2]=k=>h.value=k)},null,8,Be),[[S,h.value]])]),t("td",null,o(l.name),1),t("td",null,o(l.model),1),t("td",null,[l.barcode?(r(),u("span",ze,o(l.barcode),1)):(r(),u("span",Ue,o(n.translations.no_barcode),1))]),t("td",null,o(l.quantity),1),t("td",null,o(l.price)+" "+o(n.translations.dinar),1),t("td",null,[t("div",Ve,[l.barcode?p("",!0):(r(),u("button",{key:0,class:"btn btn-sm btn-primary",onClick:k=>pt(l),disabled:f.value},[e[40]||(e[40]=t("i",{class:"bi bi-qr-code"},null,-1)),m(" "+o(n.translations.generate),1)],8,Ne)),l.barcode?(r(),M(ot,{key:1,"barcode-data":l.barcode,"product-name":l.name,translations:n.translations,"printer-settings":a},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0),l.barcode?(r(),u("button",{key:2,class:"btn btn-sm btn-info",onClick:k=>yt(l)},[e[41]||(e[41]=t("i",{class:"bi bi-eye"},null,-1)),m(" "+o(n.translations.preview),1)],8,He)):p("",!0),l.barcode?(r(),u("button",{key:3,class:"btn btn-sm btn-secondary",onClick:k=>wt(l)},[e[42]||(e[42]=t("i",{class:"bi bi-download"},null,-1)),m(" "+o(n.translations.download),1)],8,Te)):p("",!0)])])]))),128))])])]),t("div",We,[t("nav",null,[t("ul",Oe,[t("li",{class:G(["page-item",{disabled:!n.products.prev_page_url}])},[n.products.prev_page_url?(r(),M(Z(tt),{key:0,class:"page-link",href:n.products.prev_page_url},{default:X(()=>[m(o(n.translations.previous),1)]),_:1},8,["href"])):(r(),u("span",je,o(n.translations.previous),1))],2),t("li",{class:G(["page-item",{disabled:!n.products.next_page_url}])},[n.products.next_page_url?(r(),M(Z(tt),{key:0,class:"page-link",href:n.products.next_page_url},{default:X(()=>[m(o(n.translations.next),1)]),_:1},8,["href"])):(r(),u("span",Ge,o(n.translations.next),1))],2)])])])])])])]),t("div",{class:G(["modal fade",{show:W.value}]),style:_({display:W.value?"block":"none"})},[t("div",De,[t("div",Me,[t("div",Ie,[t("h5",Le,o(n.translations.generate_barcode),1),t("button",{type:"button",class:"btn-close",onClick:e[3]||(e[3]=l=>W.value=!1)})]),t("div",Je,[t("form",{onSubmit:et(lt,["prevent"])},[t("div",Ae,[t("label",Ee,o(n.translations.product_name),1),t("input",{type:"text",class:"form-control",value:(x=i.value)==null?void 0:x.name,readonly:""},null,8,Re)]),t("div",qe,[t("label",Fe,o(n.translations.barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[4]||(e[4]=l=>b.type=l)},e[43]||(e[43]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[st,b.type]])]),t("div",Qe,[t("div",Xe,[t("div",Ke,[t("label",Ye,o(n.translations.width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[5]||(e[5]=l=>b.width=l),min:"1",max:"10"},null,512),[[y,b.width]])])]),t("div",Ze,[t("div",ts,[t("label",es,o(n.translations.height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[6]||(e[6]=l=>b.height=l),min:"10",max:"200"},null,512),[[y,b.height]])])])])],32)]),t("div",ss,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[7]||(e[7]=l=>W.value=!1)},o(n.translations.cancel),1),t("button",{type:"button",class:"btn btn-primary",onClick:lt,disabled:f.value},[f.value?(r(),u("span",as)):p("",!0),m(" "+o(n.translations.generate),1)],8,os)])])])],6),t("div",{class:G(["modal fade",{show:U.value}]),style:_({display:U.value?"block":"none"})},[t("div",ls,[t("div",ns,[t("div",is,[t("h5",rs,o(n.translations.print_barcode),1),t("button",{type:"button",class:"btn-close",onClick:e[8]||(e[8]=l=>U.value=!1)})]),t("div",ds,[t("form",{onSubmit:et(vt,["prevent"])},[t("div",cs,[t("label",us,o(n.translations.product_name),1),t("input",{type:"text",class:"form-control",value:(B=i.value)==null?void 0:B.name,readonly:""},null,8,ms)]),t("div",bs,[t("label",hs,o(n.translations.barcode),1),t("input",{type:"text",class:"form-control",value:(j=i.value)==null?void 0:j.barcode,readonly:""},null,8,ps)]),t("div",vs,[t("label",gs,o(n.translations.print_quantity),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[9]||(e[9]=l=>C.value=l),min:"1",max:"100",value:"1"},null,512),[[y,C.value]])]),t("div",fs,[t("label",ys,o(n.translations.barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[10]||(e[10]=l=>a.type=l)},e[44]||(e[44]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[st,a.type]])]),t("div",ws,[t("div",xs,[t("div",ks,[t("label",_s,o(n.translations.width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[11]||(e[11]=l=>a.width=l),min:"1",max:"10"},null,512),[[y,a.width]])])]),t("div",$s,[t("div",Ps,[t("label",Ss,o(n.translations.height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[12]||(e[12]=l=>a.height=l),min:"10",max:"200"},null,512),[[y,a.height]])])])])],32)]),t("div",Cs,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[13]||(e[13]=l=>U.value=!1)},o(n.translations.cancel),1),(D=i.value)!=null&&D.barcode?(r(),M(ot,{key:0,"barcode-data":i.value.barcode,"product-name":i.value.name,translations:n.translations,"printer-settings":a},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0)])])])],6),t("div",{class:G(["modal fade",{show:$.value}]),style:_({display:$.value?"block":"none"})},[t("div",Bs,[t("div",zs,[t("div",Us,[t("h5",Vs,o(n.translations.batch_print),1),t("button",{type:"button",class:"btn-close",onClick:e[14]||(e[14]=l=>$.value=!1)})]),t("div",Ns,[t("div",Hs,[t("label",Ts,o(n.translations.selected_products)+" ("+o(h.value.length)+")",1),t("div",Ws,[(r(!0),u(rt,null,it(h.value,l=>(r(),u("div",{key:l,class:"mb-1"},o(A(l)),1))),128))])]),t("div",Os,[t("label",js,o(n.translations.quantity_per_product),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[15]||(e[15]=l=>z.value=l),min:"1",max:"10",value:"1"},null,512),[[y,z.value]])]),h.value.length>0?(r(),u("div",Gs,[e[45]||(e[45]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-eye"}),m(" معاينة الباركود (أول منتج)")],-1)),t("div",{class:"preview-container",style:_({width:`${s.pageWidth*3.78}px`,height:`${s.pageHeight*3.78}px`,margin:"0 auto",border:"1px solid #ccc",display:"flex",flexDirection:"column",justifyContent:"center",alignItems:"center",padding:"8px",background:"#fff"})},[t("div",{class:"product-name",style:_({fontSize:`${s.fontSize}px`,fontWeight:"bold",textAlign:"center",marginBottom:"4px",maxHeight:"30px",overflow:"hidden",wordWrap:"break-word",width:"100%"})},o(A(h.value[0])),5),L.value?(r(),u("img",{key:0,src:L.value,alt:"Barcode Preview",style:_({maxWidth:"90%",height:"auto",maxHeight:s.landscape?"68px":"83px",margin:"4px auto"})},null,12,Ds)):p("",!0),s.showBarcodeNumber?(r(),u("div",{key:1,class:"barcode-text",style:_({fontSize:`${Math.max(s.fontSize-2,4)}px`,fontFamily:"monospace",textAlign:"center",marginTop:"4px",fontWeight:"bold",width:"100%"})},o((N=at(h.value[0]))==null?void 0:N.barcode),5)):p("",!0),s.showPrice&&s.price>0?(r(),u("div",{key:2,class:"price-text",style:_({fontSize:`${Math.max(s.fontSize-1,5)}px`,textAlign:"center",marginTop:"4px",fontWeight:"bold",color:"#dc3545",width:"100%"})},o(s.price)+" دينار ",5)):p("",!0)],4),t("small",Ms,"الحجم الفعلي: "+o(s.pageWidth)+"mm × "+o(s.pageHeight)+"mm",1)])):p("",!0),t("div",Is,[e[63]||(e[63]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-sliders"}),m(" إعدادات الباركود")],-1)),t("div",Ls,[t("div",Js,[e[46]||(e[46]=t("label",{class:"form-label"},[t("strong",null,"📏 عرض الورق (mm)")],-1)),d(t("input",{type:"number",class:"form-control form-control-sm",min:"20",max:"80",step:"1","onUpdate:modelValue":e[16]||(e[16]=l=>s.pageWidth=l)},null,512),[[y,s.pageWidth,void 0,{number:!0}]])]),t("div",As,[e[47]||(e[47]=t("label",{class:"form-label"},[t("strong",null,"📏 ارتفاع الورق (mm)")],-1)),d(t("input",{type:"number",class:"form-control form-control-sm",min:"20",max:"80",step:"1","onUpdate:modelValue":e[17]||(e[17]=l=>s.pageHeight=l)},null,512),[[y,s.pageHeight,void 0,{number:!0}]])])]),t("div",Es,[e[49]||(e[49]=t("small",{class:"text-muted"},"أحجام شائعة:",-1)),t("div",Rs,[t("button",{type:"button",class:"btn btn-outline-primary",onClick:e[18]||(e[18]=l=>{s.pageWidth=38,s.pageHeight=26})},e[48]||(e[48]=[t("strong",null,"38×26",-1)])),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[19]||(e[19]=l=>{s.pageWidth=35,s.pageHeight=28})}," 35×28 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[20]||(e[20]=l=>{s.pageWidth=38,s.pageHeight=28})}," 38×28 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[21]||(e[21]=l=>{s.pageWidth=40,s.pageHeight=30})}," 40×30 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[22]||(e[22]=l=>{s.pageWidth=50,s.pageHeight=30})}," 50×30 ")])]),e[64]||(e[64]=t("hr",null,null,-1)),t("div",qs,[t("div",Fs,[e[50]||(e[50]=t("label",{class:"form-label"},"ارتفاع الباركود",-1)),d(t("input",{type:"range",class:"form-range",min:"30",max:"150",step:"5","onUpdate:modelValue":e[23]||(e[23]=l=>s.height=l),onInput:Y},null,544),[[y,s.height]]),t("small",Qs,o(s.height)+"px",1)]),t("div",Xs,[e[51]||(e[51]=t("label",{class:"form-label"},"حجم الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"6",max:"20",step:"1","onUpdate:modelValue":e[24]||(e[24]=l=>s.fontSize=l),onInput:Y},null,544),[[y,s.fontSize]]),t("small",Ks,o(s.fontSize)+"px",1)])]),t("div",Ys,[t("div",Zs,[t("div",to,[d(t("input",{class:"form-check-input",type:"checkbox",id:"batchLandscapeMode","onUpdate:modelValue":e[25]||(e[25]=l=>s.landscape=l)},null,512),[[S,s.landscape]]),e[52]||(e[52]=t("label",{class:"form-check-label",for:"batchLandscapeMode"}," طباعة بالعرض (Landscape) ",-1))])]),t("div",eo,[t("div",so,[d(t("input",{class:"form-check-input",type:"checkbox",id:"batchHighQuality","onUpdate:modelValue":e[26]||(e[26]=l=>s.highQuality=l)},null,512),[[S,s.highQuality]]),e[53]||(e[53]=t("label",{class:"form-check-label",for:"batchHighQuality"}," جودة عالية (SVG) ",-1))])]),t("div",oo,[t("div",ao,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showBarcodeNumber","onUpdate:modelValue":e[27]||(e[27]=l=>s.showBarcodeNumber=l)},null,512),[[S,s.showBarcodeNumber]]),e[54]||(e[54]=t("label",{class:"form-check-label",for:"showBarcodeNumber"}," عرض رقم الباركود ",-1))])])]),s.showPrice?(r(),u("div",lo,[t("div",no,[e[55]||(e[55]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-currency-dollar"}),m(" سعر البيع")])],-1)),d(t("input",{type:"number",class:"form-control",min:"0",step:"0.01","onUpdate:modelValue":e[28]||(e[28]=l=>s.price=l),placeholder:"أدخل سعر البيع"},null,512),[[y,s.price,void 0,{number:!0}]])]),t("div",io,[t("div",ro,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":e[29]||(e[29]=l=>s.showPrice=l)},null,512),[[S,s.showPrice]]),e[56]||(e[56]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])):p("",!0),s.showPrice?p("",!0):(r(),u("div",co,[t("div",uo,[t("div",mo,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":e[30]||(e[30]=l=>s.showPrice=l)},null,512),[[S,s.showPrice]]),e[57]||(e[57]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])),t("div",bo,[e[62]||(e[62]=t("strong",null,[t("i",{class:"bi bi-info-circle"}),m(" للطابعة الحرارية MHT-L58G:")],-1)),t("ul",ho,[t("li",null,[e[58]||(e[58]=t("strong",null,"حجم الورق المحدد:",-1)),m(" "+o(s.pageWidth)+"mm × "+o(s.pageHeight)+"mm",1)]),t("li",null,[e[59]||(e[59]=t("strong",null,"اتجاه:",-1)),m(" "+o(s.landscape?"أفقي (Landscape)":"عمودي (Portrait)"),1)]),t("li",null,[e[60]||(e[60]=t("strong",null,"حجم الطباعة:",-1)),m(" "+o(s.landscape?`${s.pageWidth}mm × ${s.pageHeight}mm`:`${s.pageHeight}mm × ${s.pageWidth}mm`),1)]),e[61]||(e[61]=t("li",null,"⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة",-1))])])])]),t("div",po,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[31]||(e[31]=l=>$.value=!1)},o(n.translations.cancel),1),t("button",{type:"button",class:"btn btn-success",onClick:gt,disabled:f.value},[f.value?(r(),u("span",go)):p("",!0),e[65]||(e[65]=t("i",{class:"bi bi-printer"},null,-1)),m(" "+o(n.translations.print_all)+" ("+o(h.value.length*z.value)+") ",1)],8,vo)])])])],6),t("div",{class:G(["modal fade",{show:V.value}]),style:_({display:V.value?"block":"none"})},[t("div",fo,[t("div",yo,[t("div",wo,[t("h5",xo,o(n.translations.printer_settings),1),t("button",{type:"button",class:"btn-close",onClick:e[32]||(e[32]=l=>V.value=!1)})]),t("div",ko,[t("div",_o,[e[66]||(e[66]=t("i",{class:"bi bi-info-circle"},null,-1)),m(" "+o(n.translations.printer_settings_info),1)]),t("div",$o,[t("label",Po,o(n.translations.default_barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[33]||(e[33]=l=>g.type=l)},e[67]||(e[67]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[st,g.type]])]),t("div",So,[t("div",Co,[t("div",Bo,[t("label",zo,o(n.translations.default_width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[34]||(e[34]=l=>g.width=l),min:"1",max:"10"},null,512),[[y,g.width]])])]),t("div",Uo,[t("div",Vo,[t("label",No,o(n.translations.default_height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[35]||(e[35]=l=>g.height=l),min:"10",max:"200"},null,512),[[y,g.height]])])])])]),t("div",Ho,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[36]||(e[36]=l=>V.value=!1)},o(n.translations.cancel),1),t("button",{type:"button",class:"btn btn-primary",onClick:xt},o(n.translations.save),1)])])])],6),t("div",{class:G(["modal fade",{show:O.value}]),style:_({display:O.value?"block":"none"})},[t("div",To,[t("div",Wo,[t("div",Oo,[t("h5",jo,o(n.translations.barcode_preview),1),t("button",{type:"button",class:"btn-close",onClick:e[37]||(e[37]=l=>O.value=!1)})]),t("div",Go,[I.value?(r(),u("div",Do,[t("div",Mo,[t("span",Io,o(n.translations.loading),1)])])):J.value?(r(),u("div",Lo,[t("div",Jo,[t("h4",Ao,o(((H=i.value)==null?void 0:H.name)||"Product"),1)]),t("div",Eo,[t("img",{src:J.value,alt:n.translations.barcode_preview,class:"img-fluid",style:{width:"100%",padding:"10px",background:"white"}},null,8,Ro)]),t("div",qo,[t("div",Fo,o(n.translations.barcode)+":",1),t("div",Qo,o((q=i.value)==null?void 0:q.barcode),1)]),e[68]||(e[68]=t("div",{class:"alert alert-info small"},[t("i",{class:"bi bi-info-circle"}),m(" هذه معاينة لما سيتم طباعته ")],-1))])):p("",!0)]),t("div",Xo,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[38]||(e[38]=l=>O.value=!1)},o(n.translations.close),1),(F=i.value)!=null&&F.barcode?(r(),M(ot,{key:0,"barcode-data":i.value.barcode,"product-name":i.value.name,translations:n.translations,"printer-settings":a},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0)])])])],6)])]}),_:1},8,["translations"]))}},sa=bt(Ko,[["__scopeId","data-v-b6d890f2"]]);export{sa as default};
