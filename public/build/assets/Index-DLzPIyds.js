const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/BarcodePrinter.vue_vue_type_style_index_0_scoped_3a0f17fc_lang-hec4pmJM.js","assets/app-rfKy2ZWt.js","assets/app-DPSgePyu.css","assets/BarcodePrinter-BeVYShBq.css"])))=>i.map(i=>d[i]);
import{s as ut,r as w,o as mt,a as r,c as u,b as t,e as p,d as m,t as o,l as d,x as g,v as S,y as _,z as R,h as M,w as K,i as $t,u as Z,p as tt,j as et,f as it,F as rt,n as G,A as st,g as dt,_ as ct}from"./app-rfKy2ZWt.js";import{_ as Pt}from"./AuthenticatedLayout-CJDzBU8V.js";import{a as St}from"./BarcodePrinter.vue_vue_type_style_index_0_scoped_3a0f17fc_lang-hec4pmJM.js";import{_ as bt}from"./_plugin-vue_export-helper-DlAUqK2U.js";const Ct={class:"barcode-printer"},Bt=["disabled"],Ut={key:0,class:"spinner-border spinner-border-sm me-1"},Vt={class:"barcode-settings mt-2"},zt={class:"mb-3"},Nt={class:"text-muted"},Ht={class:"row"},Tt={class:"col-md-4"},Wt={class:"text-muted"},Ot={class:"col-md-4"},jt={class:"text-muted"},Gt={class:"col-md-4"},Dt={class:"text-muted"},Mt={class:"row mt-2"},It={class:"col-md-3"},Lt={class:"form-check"},Jt={class:"col-md-3"},At={class:"form-check"},Et={class:"col-md-3"},Rt={class:"form-check"},qt={key:0,class:"row mt-3"},Ft={class:"col-md-6"},Qt={class:"col-md-6"},Xt={class:"form-check mt-4"},Kt={key:1,class:"row mt-2"},Yt={class:"col-md-6"},Zt={class:"form-check"},te={key:2,class:"alert alert-info mt-3"},ee={class:"mb-0"},se={key:0,class:"barcode-preview mt-3 p-3 text-center",style:{background:"white",border:"2px dashed #dee2e6","border-radius":"8px"}},oe=["src"],ae={class:"text-muted mt-2 d-block"},le={__name:"BarcodePrinter",props:{barcodeData:{type:String,required:!0},productName:{type:String,default:""},translations:{type:Object,required:!0},printerSettings:{type:Object,default:()=>({width:2,height:30,type:"PNG"})}},setup(i){const v=ut(),P=i,y=w(!1),I=w(null),T=w(""),h=w(!1),n=w({width:2,height:70,fontSize:10,margin:2,landscape:!0,highQuality:!0,pageWidth:38,pageHeight:26,copies:1,showBarcodeNumber:!0,showPrice:!1,price:0}),L=()=>{h.value=!h.value},W=async()=>{if(!P.barcodeData){v.error("لا يوجد بيانات باركود للطباعة");return}y.value=!0;try{const b=V();await z(b)}catch(b){console.error("Print error:",b),v.error("حدث خطأ أثناء الطباعة")}finally{y.value=!1}},V=()=>{try{const b=document.createElementNS("http://www.w3.org/2000/svg","svg");St(b,P.barcodeData,{format:"CODE128",width:n.value.width,height:n.value.height,displayValue:!1,margin:n.value.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const a=new XMLSerializer().serializeToString(b),s=new Blob([a],{type:"image/svg+xml;charset=utf-8"}),f=URL.createObjectURL(s);return"data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(a)))}catch(b){throw console.error("JsBarcode generation error:",b),b}},$=()=>{if(P.barcodeData)try{T.value=V()}catch(b){console.error("Preview update error:",b)}},z=async b=>{try{const a=window.open("","_blank","width=300,height=400"),s=P.productName||"Product",f=P.barcodeData,C=O(b,s,f);a.document.write(C),a.document.close(),v.success("تم إرسال الباركود للطباعة")}catch(a){throw console.error("Direct print error:",a),a}},O=(b,a="Product",s="")=>{const f=n.value.landscape?"landscape":"portrait",C=`${n.value.pageWidth}mm`,U=`${n.value.pageHeight}mm`,J=n.value.landscape?`${C} ${U}`:`${U} ${C}`,Y=n.value.landscape?"18mm":"22mm";let q="";for(let A=0;A<n.value.copies;A++)q+=`
  <div class="label-page">
    <div class="label-container">
      <div class="product-name">${a}</div>
      <img class="barcode-image" src="${b}" alt="Barcode">
      ${n.value.showBarcodeNumber?`<div class="barcode-text">${s}</div>`:""}
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
      size: ${J}; 
      margin: 0; 
      orientation: ${f};
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
      height: ${U};
      margin: 0;
      padding: 0;
      overflow: hidden;
      page-break-after: always;
    }
    .label-container {
      width: ${C};
      height: ${U};
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
      max-height: ${Y};
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
</html>`};return mt(()=>{P.barcodeData&&$()}),(b,a)=>(r(),u("div",Ct,[t("button",{class:"btn btn-sm btn-success me-2",onClick:W,disabled:y.value||!i.barcodeData},[y.value?(r(),u("span",Ut)):p("",!0),a[10]||(a[10]=t("i",{class:"bi bi-printer"},null,-1)),m(" "+o(i.translations.print),1)],8,Bt),t("div",Vt,[t("div",zt,[a[11]||(a[11]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-files"}),m(" عدد النسخ")])],-1)),d(t("input",{type:"number",class:"form-control",min:"1",max:"100","onUpdate:modelValue":a[0]||(a[0]=s=>n.value.copies=s),placeholder:"أدخل عدد النسخ المطلوبة"},null,512),[[g,n.value.copies,void 0,{number:!0}]]),t("small",Nt,"سيتم طباعة "+o(n.value.copies)+" نسخة من الباركود",1)]),a[29]||(a[29]=t("hr",null,null,-1)),t("div",Ht,[t("div",Tt,[a[12]||(a[12]=t("label",{class:"form-label"},"عرض الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"1",max:"5",step:"0.1","onUpdate:modelValue":a[1]||(a[1]=s=>n.value.width=s),onInput:$},null,544),[[g,n.value.width]]),t("small",Wt,o(n.value.width),1)]),t("div",Ot,[a[13]||(a[13]=t("label",{class:"form-label"},"ارتفاع الباركود",-1)),d(t("input",{type:"range",class:"form-range",min:"30",max:"150",step:"5","onUpdate:modelValue":a[2]||(a[2]=s=>n.value.height=s),onInput:$},null,544),[[g,n.value.height]]),t("small",jt,o(n.value.height)+"px",1)]),t("div",Gt,[a[14]||(a[14]=t("label",{class:"form-label"},"حجم الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"6",max:"20",step:"1","onUpdate:modelValue":a[3]||(a[3]=s=>n.value.fontSize=s),onInput:$},null,544),[[g,n.value.fontSize]]),t("small",Dt,o(n.value.fontSize)+"px",1)])]),t("div",Mt,[t("div",It,[t("div",Lt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"landscapeMode","onUpdate:modelValue":a[4]||(a[4]=s=>n.value.landscape=s),onChange:$},null,544),[[S,n.value.landscape]]),a[15]||(a[15]=t("label",{class:"form-check-label",for:"landscapeMode"}," طباعة بالعرض (Landscape) ",-1))])]),t("div",Jt,[t("div",At,[d(t("input",{class:"form-check-input",type:"checkbox",id:"highQuality","onUpdate:modelValue":a[5]||(a[5]=s=>n.value.highQuality=s),onChange:$},null,544),[[S,n.value.highQuality]]),a[16]||(a[16]=t("label",{class:"form-check-label",for:"highQuality"}," جودة عالية ",-1))])]),t("div",Et,[t("div",Rt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showBarcodeNumber","onUpdate:modelValue":a[6]||(a[6]=s=>n.value.showBarcodeNumber=s)},null,512),[[S,n.value.showBarcodeNumber]]),a[17]||(a[17]=t("label",{class:"form-check-label",for:"showBarcodeNumber"}," عرض رقم الباركود ",-1))])]),t("div",{class:"col-md-3"},[t("button",{class:"btn btn-sm btn-info",onClick:L},a[18]||(a[18]=[t("i",{class:"bi bi-info-circle"},null,-1),m(" معلومات الطابعة ")]))])]),n.value.showPrice?(r(),u("div",qt,[t("div",Ft,[a[19]||(a[19]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-currency-dollar"}),m(" سعر البيع")])],-1)),d(t("input",{type:"number",class:"form-control",min:"0",step:"0.01","onUpdate:modelValue":a[7]||(a[7]=s=>n.value.price=s),placeholder:"أدخل سعر البيع",onInput:$},null,544),[[g,n.value.price,void 0,{number:!0}]])]),t("div",Qt,[t("div",Xt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":a[8]||(a[8]=s=>n.value.showPrice=s)},null,512),[[S,n.value.showPrice]]),a[20]||(a[20]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])):p("",!0),n.value.showPrice?p("",!0):(r(),u("div",Kt,[t("div",Yt,[t("div",Zt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":a[9]||(a[9]=s=>n.value.showPrice=s)},null,512),[[S,n.value.showPrice]]),a[21]||(a[21]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])),h.value?(r(),u("div",te,[a[28]||(a[28]=t("h6",null,[t("i",{class:"bi bi-printer"}),m(" معلومات الطابعة الحرارية:")],-1)),t("ul",ee,[a[25]||(a[25]=t("li",null,[t("strong",null,"الطابعة:"),m(" Thermal Printer (MHT-L58G)")],-1)),t("li",null,[a[22]||(a[22]=t("strong",null,"حجم الورق المحدد:",-1)),m(" "+o(n.value.pageWidth)+"mm × "+o(n.value.pageHeight)+"mm",1)]),t("li",null,[a[23]||(a[23]=t("strong",null,"الاتجاه:",-1)),m(" "+o(n.value.landscape?"أفقي (Landscape)":"عمودي (Portrait)"),1)]),t("li",null,[a[24]||(a[24]=t("strong",null,"حجم الطباعة:",-1)),m(" "+o(n.value.landscape?`${n.value.pageWidth}mm × ${n.value.pageHeight}mm`:`${n.value.pageHeight}mm × ${n.value.pageWidth}mm`),1)]),a[26]||(a[26]=t("li",null,[t("strong",null,"دقة الطباعة الموصى بها:"),m(" 203 DPI")],-1)),a[27]||(a[27]=t("li",null,"⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة",-1))])])):p("",!0)]),T.value?(r(),u("div",se,[a[30]||(a[30]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-eye"}),m(" معاينة الباركود")],-1)),t("div",{class:"preview-container",style:_({width:`${n.value.pageWidth*3.78}px`,height:`${n.value.pageHeight*3.78}px`,margin:"0 auto",border:"1px solid #ccc",display:"flex",flexDirection:"column",justifyContent:"center",alignItems:"center",padding:"8px",background:"#fff"})},[t("div",{class:"product-name",style:_({fontSize:`${n.value.fontSize}px`,fontWeight:"bold",textAlign:"center",marginBottom:"4px",maxHeight:"30px",overflow:"hidden",wordWrap:"break-word",width:"100%"})},o(i.productName),5),t("img",{src:T.value,alt:"Barcode Preview",style:_({maxWidth:"90%",height:"auto",maxHeight:n.value.landscape?"68px":"83px",margin:"4px auto"})},null,12,oe),n.value.showBarcodeNumber?(r(),u("div",{key:0,class:"barcode-text",style:_({fontSize:`${Math.max(n.value.fontSize-2,4)}px`,fontFamily:"monospace",textAlign:"center",marginTop:"4px",fontWeight:"bold",width:"100%"})},o(i.barcodeData),5)):p("",!0),n.value.showPrice&&n.value.price>0?(r(),u("div",{key:1,class:"price-text",style:_({fontSize:`${Math.max(n.value.fontSize-1,5)}px`,textAlign:"center",marginTop:"4px",fontWeight:"bold",color:"#dc3545",width:"100%"})},o(n.value.price)+" دينار ",5)):p("",!0)],4),t("small",ae,"الحجم الفعلي: "+o(n.value.pageWidth)+"mm × "+o(n.value.pageHeight)+"mm",1)])):p("",!0),t("canvas",{ref_key:"barcodeCanvas",ref:I,style:{display:"none"}},null,512)]))}},ot=bt(le,[["__scopeId","data-v-3a0f17fc"]]),ne={class:"pagetitle dark:text-white"},ie={class:"dark:text-white"},re={class:"breadcrumb"},de={class:"breadcrumb-item"},ce={class:"breadcrumb-item active dark:text-white"},ue={class:"section dashboard"},me={class:"row"},be={class:"col-12"},he={class:"card"},pe={class:"card-body"},ve={class:"row"},ge={class:"col-md-4"},fe=["placeholder"],ye={class:"col-md-2"},we={type:"submit",class:"btn btn-primary"},xe={class:"col-md-3"},ke=["disabled"],_e={class:"col-md-3"},$e={class:"col-12"},Pe={class:"card"},Se={class:"card-body"},Ce={class:"table-responsive"},Be={class:"table text-center"},Ue=["checked"],Ve=["value"],ze={key:0,class:"badge bg-success"},Ne={key:1,class:"badge bg-warning"},He={class:"btn-group",role:"group"},Te=["onClick","disabled"],We=["onClick"],Oe=["onClick"],je={class:"d-flex justify-content-center mt-3"},Ge={class:"pagination"},De={key:1,class:"page-link"},Me={key:1,class:"page-link"},Ie={class:"modal-dialog"},Le={class:"modal-content"},Je={class:"modal-header"},Ae={class:"modal-title"},Ee={class:"modal-body"},Re={class:"mb-3"},qe={class:"form-label"},Fe=["value"],Qe={class:"mb-3"},Xe={class:"form-label"},Ke={class:"row"},Ye={class:"col-md-6"},Ze={class:"mb-3"},ts={class:"form-label"},es={class:"col-md-6"},ss={class:"mb-3"},os={class:"form-label"},as={class:"modal-footer"},ls=["disabled"],ns={key:0,class:"spinner-border spinner-border-sm me-2"},is={class:"modal-dialog"},rs={class:"modal-content"},ds={class:"modal-header"},cs={class:"modal-title"},us={class:"modal-body"},ms={class:"mb-3"},bs={class:"form-label"},hs=["value"],ps={class:"mb-3"},vs={class:"form-label"},gs=["value"],fs={class:"mb-3"},ys={class:"form-label"},ws={class:"mb-3"},xs={class:"form-label"},ks={class:"row"},_s={class:"col-md-6"},$s={class:"mb-3"},Ps={class:"form-label"},Ss={class:"col-md-6"},Cs={class:"mb-3"},Bs={class:"form-label"},Us={class:"modal-footer"},Vs={class:"modal-dialog modal-lg"},zs={class:"modal-content"},Ns={class:"modal-header"},Hs={class:"modal-title"},Ts={class:"modal-body"},Ws={class:"mb-3"},Os={class:"form-label"},js={class:"border p-2",style:{"max-height":"200px","overflow-y":"auto"}},Gs={class:"mb-3"},Ds={class:"form-label"},Ms={key:0,class:"barcode-preview mb-3 p-3 text-center",style:{background:"white",border:"2px dashed #dee2e6","border-radius":"8px"}},Is=["src"],Ls={class:"text-muted mt-2 d-block"},Js={class:"barcode-settings mt-3 p-3",style:{background:"#f8f9fa","border-radius":"8px",border:"1px solid #dee2e6"}},As={class:"row mb-2"},Es={class:"col-md-6"},Rs={class:"col-md-6"},qs={class:"mb-3"},Fs={class:"btn-group btn-group-sm mt-1",role:"group"},Qs={class:"row"},Xs={class:"col-md-4"},Ks={class:"text-muted"},Ys={class:"col-md-4"},Zs={class:"text-muted"},to={class:"col-md-4"},eo={class:"text-muted"},so={class:"row mt-3"},oo={class:"col-md-4"},ao={class:"form-check"},lo={class:"col-md-4"},no={class:"form-check"},io={class:"col-md-4"},ro={class:"form-check"},co={key:0,class:"row mt-3"},uo={class:"col-md-6"},mo={class:"col-md-6"},bo={class:"form-check mt-4"},ho={key:1,class:"row mt-2"},po={class:"col-md-6"},vo={class:"form-check"},go={class:"alert alert-info mt-3 mb-0",style:{"font-size":"0.9em"}},fo={class:"mb-0 mt-2",style:{"font-size":"0.85em"}},yo={class:"modal-footer"},wo=["disabled"],xo={key:0,class:"spinner-border spinner-border-sm me-2"},ko={class:"modal-dialog"},_o={class:"modal-content"},$o={class:"modal-header"},Po={class:"modal-title"},So={class:"modal-body"},Co={class:"alert alert-info"},Bo={class:"mb-3"},Uo={class:"form-label"},Vo={class:"row"},zo={class:"col-md-6"},No={class:"mb-3"},Ho={class:"form-label"},To={class:"col-md-6"},Wo={class:"mb-3"},Oo={class:"form-label"},jo={class:"modal-footer"},Go={class:"modal-dialog modal-lg"},Do={class:"modal-content"},Mo={class:"modal-header"},Io={class:"modal-title"},Lo={class:"modal-body text-center"},Jo={key:0,class:"text-center"},Ao={class:"spinner-border",role:"status"},Eo={class:"visually-hidden"},Ro={key:1,class:"border p-4",style:{"max-width":"400px",margin:"0 auto"}},qo={class:"mb-3"},Fo={class:"fw-bold text-center"},Qo={class:"mb-3"},Xo=["src","alt"],Ko={class:"mb-3"},Yo={class:"text-muted small"},Zo={class:"fw-bold font-monospace fs-5"},ta={class:"modal-footer"},ea={__name:"Index",props:{products:Object,translations:Object,filters:Object},setup(i){var nt;const v=ut(),P=i,y=w(!1),I=w(!1),T=R({search:((nt=P.filters)==null?void 0:nt.search)||""}),h=w([]),n=w(null),L=w(""),W=w(!1),V=w(!1),$=w(!1),z=w(!1),O=w(!1),b=R({type:"PNG",width:2,height:30}),a=R({type:"PNG",width:2,height:30}),s=R({type:"SVG",width:2,height:70,fontSize:10,margin:2,landscape:!0,highQuality:!0,pageWidth:38,pageHeight:26,copies:1,showBarcodeNumber:!0,showPrice:!1,price:0}),f=R({type:"PNG",width:2,height:30}),C=w(1),U=w(1),J=w(""),Y=()=>{dt.get(route("barcode.index"),T,{preserveState:!0,replace:!0})},q=()=>{h.value.length===P.products.data.length?h.value=[]:h.value=P.products.data.map(c=>c.id)},A=c=>{const e=P.products.data.find(x=>x.id===c);return e?e.name:""},at=c=>P.products.data.find(e=>e.id===c),ht=()=>{$.value=!0,E()},E=async()=>{if(h.value.length===0)return;const c=at(h.value[0]);if(!(!c||!c.barcode))try{const e=(await ct(async()=>{const{default:j}=await import("./BarcodePrinter.vue_vue_type_style_index_0_scoped_3a0f17fc_lang-hec4pmJM.js").then(D=>D.J);return{default:j}},__vite__mapDeps([0,1,2,3]))).default,x=document.createElementNS("http://www.w3.org/2000/svg","svg");e(x,c.barcode,{format:"CODE128",width:s.width,height:s.height,displayValue:!1,margin:s.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const B=new XMLSerializer().serializeToString(x);L.value="data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(B)))}catch(e){console.error("Batch preview generation error:",e)}},pt=async c=>{y.value=!0;try{const x=await(await fetch(route("barcode.generate"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:c.id,type:"PNG",width:2,height:30})})).json();x.success?(v.success("تم توليد الباركود بنجاح"),window.location.reload()):v.error(x.message||"حدث خطأ أثناء توليد الباركود")}catch(e){console.error("Generate barcode error:",e),v.error("حدث خطأ أثناء توليد الباركود")}finally{y.value=!1}},lt=async()=>{y.value=!0;try{const e=await(await fetch(route("barcode.generate"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:n.value.id,type:b.type,width:b.width,height:b.height})})).json();e.success?(v.success(e.message),W.value=!1,dt.reload()):v.error(e.message)}catch{v.error("حدث خطأ أثناء توليد الباركود")}finally{y.value=!1}},vt=async()=>{y.value=!0;try{const e=await(await fetch(route("barcode.print"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:n.value.id,quantity:C.value,printer_settings:{type:a.type,width:a.width,height:a.height}})})).json();e.success?(v.success(e.message),V.value=!1):v.error(e.message)}catch{v.error("حدث خطأ أثناء الطباعة")}finally{y.value=!1}},gt=async()=>{y.value=!0;try{const e=await(await fetch(route("barcode.batch.print"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_ids:h.value,quantity_per_product:U.value,printer_settings:{type:s.type,width:s.width,height:s.height}})})).json();e.success?(v.success(e.message),await ft(e.results),$.value=!1,h.value=[]):v.error(e.message)}catch{v.error("حدث خطأ أثناء الطباعة المجمعة")}finally{y.value=!1}},ft=async c=>{try{const e=(await ct(async()=>{const{default:k}=await import("./BarcodePrinter.vue_vue_type_style_index_0_scoped_3a0f17fc_lang-hec4pmJM.js").then(X=>X.J);return{default:k}},__vite__mapDeps([0,1,2,3]))).default,x=[];for(const k of c)if(k.success&&k.product){const X=document.createElementNS("http://www.w3.org/2000/svg","svg");e(X,k.product.barcode,{format:"CODE128",width:s.width,height:s.height,displayValue:!1,margin:s.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const kt=new XMLSerializer().serializeToString(X),_t="data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(kt)));x.push({...k,svgUrl:_t})}const B=window.open("","_blank","width=400,height=600");if(!B){v.error("فشل فتح نافذة الطباعة. يرجى السماح بالنوافذ المنبثقة.");return}let j="";for(const k of x)j+=`
        <div class="label-page">
          <div class="label-container">
            <div class="product-name">${k.product.name}</div>
            <img class="barcode-image" src="${k.svgUrl}" alt="Barcode">
            ${s.showBarcodeNumber?`<div class="barcode-text">${k.product.barcode}</div>`:""}
            ${s.showPrice&&s.price>0?`<div class="price-text">${s.price} دينار</div>`:""}
          </div>
          </div>
        `;const D=s.landscape?"landscape":"portrait",N=`${s.pageWidth}mm`,H=`${s.pageHeight}mm`,F=s.landscape?`${N} ${H}`:`${H} ${N}`,Q=s.landscape?"18mm":"22mm",l=`
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركودات</title>
      <style>
    /* Optimized for thermal printers - Batch Print with Dynamic Settings */
    @page { 
      size: ${F}; 
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
      max-height: ${Q};
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
    `;B.document.write(l),B.document.close()}catch(e){console.error("Batch print error:",e),v.error("حدث خطأ أثناء طباعة الباركودات")}},yt=async c=>{n.value=c,I.value=!0,O.value=!0;try{const e=new URLSearchParams({code:c.barcode,type:f.type,width:f.width,height:f.height}),x=await fetch(`${route("barcode.preview")}?${e}`);if(x.ok){const B=await x.blob();J.value=URL.createObjectURL(B)}else v.error("فشل في تحميل معاينة الباركود")}catch{v.error("حدث خطأ أثناء تحميل المعاينة")}finally{I.value=!1}},wt=c=>{const e=new URLSearchParams({type:f.type,width:f.width,height:f.height});window.open(`${route("barcode.download",c.id)}?${e}`,"_blank")},xt=()=>{localStorage.setItem("printerSettings",JSON.stringify(f)),v.success("تم حفظ إعدادات الطابعة"),z.value=!1};return mt(()=>{const c=localStorage.getItem("printerSettings");if(c){const e=JSON.parse(c);Object.assign(f,e),Object.assign(b,e),Object.assign(a,e),Object.assign(s,e)}}),(c,e)=>(r(),M(Pt,{translations:i.translations},{default:K(()=>{var x,B,j,D,N,H,F,Q;return[t("div",ne,[t("h1",ie,o(i.translations.barcode_generation),1),t("nav",null,[t("ol",re,[t("li",de,[$t(Z(tt),{class:"nav-link dark:text-white",href:c.route("dashboard")},{default:K(()=>[m(o(i.translations.Home),1)]),_:1},8,["href"])]),t("li",ce,o(i.translations.barcode_generation),1)])])]),t("section",ue,[t("div",me,[t("div",be,[t("div",he,[t("div",pe,[t("form",{onSubmit:et(Y,["prevent"])},[t("div",ve,[t("div",ge,[d(t("input",{type:"text",class:"form-control","onUpdate:modelValue":e[0]||(e[0]=l=>T.search=l),placeholder:i.translations.search},null,8,fe),[[g,T.search]])]),t("div",ye,[t("button",we,[m(o(i.translations.search)+" ",1),e[40]||(e[40]=t("i",{class:"bi bi-search"},null,-1))])]),t("div",xe,[t("button",{type:"button",class:"btn btn-success",onClick:ht,disabled:h.value.length===0},o(i.translations.batch_print)+" ("+o(h.value.length)+") ",9,ke)]),t("div",_e,[t("button",{type:"button",class:"btn btn-info",onClick:e[1]||(e[1]=l=>z.value=!0)},o(i.translations.printer_settings),1)])])],32)])])]),t("div",$e,[t("div",Pe,[t("div",Se,[t("div",Ce,[t("table",Be,[t("thead",null,[t("tr",null,[t("th",null,[t("input",{type:"checkbox",onChange:q,checked:h.value.length===i.products.data.length&&i.products.data.length>0},null,40,Ue)]),t("th",null,o(i.translations.name),1),t("th",null,o(i.translations.model),1),t("th",null,o(i.translations.barcode),1),t("th",null,o(i.translations.quantity),1),t("th",null,o(i.translations.price),1),t("th",null,o(i.translations.actions),1)])]),t("tbody",null,[(r(!0),u(rt,null,it(i.products.data,l=>(r(),u("tr",{key:l.id},[t("td",null,[d(t("input",{type:"checkbox",value:l.id,"onUpdate:modelValue":e[2]||(e[2]=k=>h.value=k)},null,8,Ve),[[S,h.value]])]),t("td",null,o(l.name),1),t("td",null,o(l.model),1),t("td",null,[l.barcode?(r(),u("span",ze,o(l.barcode),1)):(r(),u("span",Ne,o(i.translations.no_barcode),1))]),t("td",null,o(l.quantity),1),t("td",null,o(l.price)+" "+o(i.translations.dinar),1),t("td",null,[t("div",He,[l.barcode?p("",!0):(r(),u("button",{key:0,class:"btn btn-sm btn-primary",onClick:k=>pt(l),disabled:y.value},[e[41]||(e[41]=t("i",{class:"bi bi-qr-code"},null,-1)),m(" "+o(i.translations.generate),1)],8,Te)),l.barcode?(r(),M(ot,{key:1,"barcode-data":l.barcode,"product-name":l.name,translations:i.translations,"printer-settings":a},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0),l.barcode?(r(),u("button",{key:2,class:"btn btn-sm btn-info",onClick:k=>yt(l)},[e[42]||(e[42]=t("i",{class:"bi bi-eye"},null,-1)),m(" "+o(i.translations.preview),1)],8,We)):p("",!0),l.barcode?(r(),u("button",{key:3,class:"btn btn-sm btn-secondary",onClick:k=>wt(l)},[e[43]||(e[43]=t("i",{class:"bi bi-download"},null,-1)),m(" "+o(i.translations.download),1)],8,Oe)):p("",!0)])])]))),128))])])]),t("div",je,[t("nav",null,[t("ul",Ge,[t("li",{class:G(["page-item",{disabled:!i.products.prev_page_url}])},[i.products.prev_page_url?(r(),M(Z(tt),{key:0,class:"page-link",href:i.products.prev_page_url},{default:K(()=>[m(o(i.translations.previous),1)]),_:1},8,["href"])):(r(),u("span",De,o(i.translations.previous),1))],2),t("li",{class:G(["page-item",{disabled:!i.products.next_page_url}])},[i.products.next_page_url?(r(),M(Z(tt),{key:0,class:"page-link",href:i.products.next_page_url},{default:K(()=>[m(o(i.translations.next),1)]),_:1},8,["href"])):(r(),u("span",Me,o(i.translations.next),1))],2)])])])])])])]),t("div",{class:G(["modal fade",{show:W.value}]),style:_({display:W.value?"block":"none"})},[t("div",Ie,[t("div",Le,[t("div",Je,[t("h5",Ae,o(i.translations.generate_barcode),1),t("button",{type:"button",class:"btn-close",onClick:e[3]||(e[3]=l=>W.value=!1)})]),t("div",Ee,[t("form",{onSubmit:et(lt,["prevent"])},[t("div",Re,[t("label",qe,o(i.translations.product_name),1),t("input",{type:"text",class:"form-control",value:(x=n.value)==null?void 0:x.name,readonly:""},null,8,Fe)]),t("div",Qe,[t("label",Xe,o(i.translations.barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[4]||(e[4]=l=>b.type=l)},e[44]||(e[44]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[st,b.type]])]),t("div",Ke,[t("div",Ye,[t("div",Ze,[t("label",ts,o(i.translations.width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[5]||(e[5]=l=>b.width=l),min:"1",max:"10"},null,512),[[g,b.width]])])]),t("div",es,[t("div",ss,[t("label",os,o(i.translations.height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[6]||(e[6]=l=>b.height=l),min:"10",max:"200"},null,512),[[g,b.height]])])])])],32)]),t("div",as,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[7]||(e[7]=l=>W.value=!1)},o(i.translations.cancel),1),t("button",{type:"button",class:"btn btn-primary",onClick:lt,disabled:y.value},[y.value?(r(),u("span",ns)):p("",!0),m(" "+o(i.translations.generate),1)],8,ls)])])])],6),t("div",{class:G(["modal fade",{show:V.value}]),style:_({display:V.value?"block":"none"})},[t("div",is,[t("div",rs,[t("div",ds,[t("h5",cs,o(i.translations.print_barcode),1),t("button",{type:"button",class:"btn-close",onClick:e[8]||(e[8]=l=>V.value=!1)})]),t("div",us,[t("form",{onSubmit:et(vt,["prevent"])},[t("div",ms,[t("label",bs,o(i.translations.product_name),1),t("input",{type:"text",class:"form-control",value:(B=n.value)==null?void 0:B.name,readonly:""},null,8,hs)]),t("div",ps,[t("label",vs,o(i.translations.barcode),1),t("input",{type:"text",class:"form-control",value:(j=n.value)==null?void 0:j.barcode,readonly:""},null,8,gs)]),t("div",fs,[t("label",ys,o(i.translations.print_quantity),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[9]||(e[9]=l=>C.value=l),min:"1",max:"100",value:"1"},null,512),[[g,C.value]])]),t("div",ws,[t("label",xs,o(i.translations.barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[10]||(e[10]=l=>a.type=l)},e[45]||(e[45]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[st,a.type]])]),t("div",ks,[t("div",_s,[t("div",$s,[t("label",Ps,o(i.translations.width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[11]||(e[11]=l=>a.width=l),min:"1",max:"10"},null,512),[[g,a.width]])])]),t("div",Ss,[t("div",Cs,[t("label",Bs,o(i.translations.height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[12]||(e[12]=l=>a.height=l),min:"10",max:"200"},null,512),[[g,a.height]])])])])],32)]),t("div",Us,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[13]||(e[13]=l=>V.value=!1)},o(i.translations.cancel),1),(D=n.value)!=null&&D.barcode?(r(),M(ot,{key:0,"barcode-data":n.value.barcode,"product-name":n.value.name,translations:i.translations,"printer-settings":a},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0)])])])],6),t("div",{class:G(["modal fade",{show:$.value}]),style:_({display:$.value?"block":"none"})},[t("div",Vs,[t("div",zs,[t("div",Ns,[t("h5",Hs,o(i.translations.batch_print),1),t("button",{type:"button",class:"btn-close",onClick:e[14]||(e[14]=l=>$.value=!1)})]),t("div",Ts,[t("div",Ws,[t("label",Os,o(i.translations.selected_products)+" ("+o(h.value.length)+")",1),t("div",js,[(r(!0),u(rt,null,it(h.value,l=>(r(),u("div",{key:l,class:"mb-1"},o(A(l)),1))),128))])]),t("div",Gs,[t("label",Ds,o(i.translations.quantity_per_product),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[15]||(e[15]=l=>U.value=l),min:"1",max:"10",value:"1"},null,512),[[g,U.value]])]),h.value.length>0?(r(),u("div",Ms,[e[46]||(e[46]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-eye"}),m(" معاينة الباركود (أول منتج)")],-1)),t("div",{class:"preview-container",style:_({width:`${s.pageWidth*3.78}px`,height:`${s.pageHeight*3.78}px`,margin:"0 auto",border:"1px solid #ccc",display:"flex",flexDirection:"column",justifyContent:"center",alignItems:"center",padding:"8px",background:"#fff"})},[t("div",{class:"product-name",style:_({fontSize:`${s.fontSize}px`,fontWeight:"bold",textAlign:"center",marginBottom:"4px",maxHeight:"30px",overflow:"hidden",wordWrap:"break-word",width:"100%"})},o(A(h.value[0])),5),L.value?(r(),u("img",{key:0,src:L.value,alt:"Barcode Preview",style:_({maxWidth:"90%",height:"auto",maxHeight:s.landscape?"68px":"83px",margin:"4px auto"})},null,12,Is)):p("",!0),s.showBarcodeNumber?(r(),u("div",{key:1,class:"barcode-text",style:_({fontSize:`${Math.max(s.fontSize-2,4)}px`,fontFamily:"monospace",textAlign:"center",marginTop:"4px",fontWeight:"bold",width:"100%"})},o((N=at(h.value[0]))==null?void 0:N.barcode),5)):p("",!0),s.showPrice&&s.price>0?(r(),u("div",{key:2,class:"price-text",style:_({fontSize:`${Math.max(s.fontSize-1,5)}px`,textAlign:"center",marginTop:"4px",fontWeight:"bold",color:"#dc3545",width:"100%"})},o(s.price)+" دينار ",5)):p("",!0)],4),t("small",Ls,"الحجم الفعلي: "+o(s.pageWidth)+"mm × "+o(s.pageHeight)+"mm",1)])):p("",!0),t("div",Js,[e[65]||(e[65]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-sliders"}),m(" إعدادات الباركود")],-1)),t("div",As,[t("div",Es,[e[47]||(e[47]=t("label",{class:"form-label"},[t("strong",null,"📏 عرض الورق (mm)")],-1)),d(t("input",{type:"number",class:"form-control form-control-sm",min:"20",max:"80",step:"1","onUpdate:modelValue":e[16]||(e[16]=l=>s.pageWidth=l)},null,512),[[g,s.pageWidth,void 0,{number:!0}]])]),t("div",Rs,[e[48]||(e[48]=t("label",{class:"form-label"},[t("strong",null,"📏 ارتفاع الورق (mm)")],-1)),d(t("input",{type:"number",class:"form-control form-control-sm",min:"20",max:"80",step:"1","onUpdate:modelValue":e[17]||(e[17]=l=>s.pageHeight=l)},null,512),[[g,s.pageHeight,void 0,{number:!0}]])])]),t("div",qs,[e[50]||(e[50]=t("small",{class:"text-muted"},"أحجام شائعة:",-1)),t("div",Fs,[t("button",{type:"button",class:"btn btn-outline-primary",onClick:e[18]||(e[18]=l=>{s.pageWidth=38,s.pageHeight=26})},e[49]||(e[49]=[t("strong",null,"38×26",-1)])),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[19]||(e[19]=l=>{s.pageWidth=35,s.pageHeight=28})}," 35×28 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[20]||(e[20]=l=>{s.pageWidth=38,s.pageHeight=28})}," 38×28 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[21]||(e[21]=l=>{s.pageWidth=40,s.pageHeight=30})}," 40×30 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[22]||(e[22]=l=>{s.pageWidth=50,s.pageHeight=30})}," 50×30 ")])]),e[66]||(e[66]=t("hr",null,null,-1)),t("div",Qs,[t("div",Xs,[e[51]||(e[51]=t("label",{class:"form-label"},"عرض الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"1",max:"5",step:"0.1","onUpdate:modelValue":e[23]||(e[23]=l=>s.width=l),onInput:E},null,544),[[g,s.width]]),t("small",Ks,o(s.width),1)]),t("div",Ys,[e[52]||(e[52]=t("label",{class:"form-label"},"ارتفاع الباركود",-1)),d(t("input",{type:"range",class:"form-range",min:"30",max:"150",step:"5","onUpdate:modelValue":e[24]||(e[24]=l=>s.height=l),onInput:E},null,544),[[g,s.height]]),t("small",Zs,o(s.height)+"px",1)]),t("div",to,[e[53]||(e[53]=t("label",{class:"form-label"},"حجم الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"6",max:"20",step:"1","onUpdate:modelValue":e[25]||(e[25]=l=>s.fontSize=l),onInput:E},null,544),[[g,s.fontSize]]),t("small",eo,o(s.fontSize)+"px",1)])]),t("div",so,[t("div",oo,[t("div",ao,[d(t("input",{class:"form-check-input",type:"checkbox",id:"batchLandscapeMode","onUpdate:modelValue":e[26]||(e[26]=l=>s.landscape=l)},null,512),[[S,s.landscape]]),e[54]||(e[54]=t("label",{class:"form-check-label",for:"batchLandscapeMode"}," طباعة بالعرض (Landscape) ",-1))])]),t("div",lo,[t("div",no,[d(t("input",{class:"form-check-input",type:"checkbox",id:"batchHighQuality","onUpdate:modelValue":e[27]||(e[27]=l=>s.highQuality=l)},null,512),[[S,s.highQuality]]),e[55]||(e[55]=t("label",{class:"form-check-label",for:"batchHighQuality"}," جودة عالية (SVG) ",-1))])]),t("div",io,[t("div",ro,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showBarcodeNumber","onUpdate:modelValue":e[28]||(e[28]=l=>s.showBarcodeNumber=l)},null,512),[[S,s.showBarcodeNumber]]),e[56]||(e[56]=t("label",{class:"form-check-label",for:"showBarcodeNumber"}," عرض رقم الباركود ",-1))])])]),s.showPrice?(r(),u("div",co,[t("div",uo,[e[57]||(e[57]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-currency-dollar"}),m(" سعر البيع")])],-1)),d(t("input",{type:"number",class:"form-control",min:"0",step:"0.01","onUpdate:modelValue":e[29]||(e[29]=l=>s.price=l),placeholder:"أدخل سعر البيع",onInput:E},null,544),[[g,s.price,void 0,{number:!0}]])]),t("div",mo,[t("div",bo,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":e[30]||(e[30]=l=>s.showPrice=l)},null,512),[[S,s.showPrice]]),e[58]||(e[58]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])):p("",!0),s.showPrice?p("",!0):(r(),u("div",ho,[t("div",po,[t("div",vo,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":e[31]||(e[31]=l=>s.showPrice=l)},null,512),[[S,s.showPrice]]),e[59]||(e[59]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])),t("div",go,[e[64]||(e[64]=t("strong",null,[t("i",{class:"bi bi-info-circle"}),m(" للطابعة الحرارية MHT-L58G:")],-1)),t("ul",fo,[t("li",null,[e[60]||(e[60]=t("strong",null,"حجم الورق المحدد:",-1)),m(" "+o(s.pageWidth)+"mm × "+o(s.pageHeight)+"mm",1)]),t("li",null,[e[61]||(e[61]=t("strong",null,"اتجاه:",-1)),m(" "+o(s.landscape?"أفقي (Landscape)":"عمودي (Portrait)"),1)]),t("li",null,[e[62]||(e[62]=t("strong",null,"حجم الطباعة:",-1)),m(" "+o(s.landscape?`${s.pageWidth}mm × ${s.pageHeight}mm`:`${s.pageHeight}mm × ${s.pageWidth}mm`),1)]),e[63]||(e[63]=t("li",null,"⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة",-1))])])])]),t("div",yo,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[32]||(e[32]=l=>$.value=!1)},o(i.translations.cancel),1),t("button",{type:"button",class:"btn btn-success",onClick:gt,disabled:y.value},[y.value?(r(),u("span",xo)):p("",!0),e[67]||(e[67]=t("i",{class:"bi bi-printer"},null,-1)),m(" "+o(i.translations.print_all)+" ("+o(h.value.length*U.value)+") ",1)],8,wo)])])])],6),t("div",{class:G(["modal fade",{show:z.value}]),style:_({display:z.value?"block":"none"})},[t("div",ko,[t("div",_o,[t("div",$o,[t("h5",Po,o(i.translations.printer_settings),1),t("button",{type:"button",class:"btn-close",onClick:e[33]||(e[33]=l=>z.value=!1)})]),t("div",So,[t("div",Co,[e[68]||(e[68]=t("i",{class:"bi bi-info-circle"},null,-1)),m(" "+o(i.translations.printer_settings_info),1)]),t("div",Bo,[t("label",Uo,o(i.translations.default_barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[34]||(e[34]=l=>f.type=l)},e[69]||(e[69]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[st,f.type]])]),t("div",Vo,[t("div",zo,[t("div",No,[t("label",Ho,o(i.translations.default_width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[35]||(e[35]=l=>f.width=l),min:"1",max:"10"},null,512),[[g,f.width]])])]),t("div",To,[t("div",Wo,[t("label",Oo,o(i.translations.default_height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[36]||(e[36]=l=>f.height=l),min:"10",max:"200"},null,512),[[g,f.height]])])])])]),t("div",jo,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[37]||(e[37]=l=>z.value=!1)},o(i.translations.cancel),1),t("button",{type:"button",class:"btn btn-primary",onClick:xt},o(i.translations.save),1)])])])],6),t("div",{class:G(["modal fade",{show:O.value}]),style:_({display:O.value?"block":"none"})},[t("div",Go,[t("div",Do,[t("div",Mo,[t("h5",Io,o(i.translations.barcode_preview),1),t("button",{type:"button",class:"btn-close",onClick:e[38]||(e[38]=l=>O.value=!1)})]),t("div",Lo,[I.value?(r(),u("div",Jo,[t("div",Ao,[t("span",Eo,o(i.translations.loading),1)])])):J.value?(r(),u("div",Ro,[t("div",qo,[t("h4",Fo,o(((H=n.value)==null?void 0:H.name)||"Product"),1)]),t("div",Qo,[t("img",{src:J.value,alt:i.translations.barcode_preview,class:"img-fluid",style:{width:"100%",padding:"10px",background:"white"}},null,8,Xo)]),t("div",Ko,[t("div",Yo,o(i.translations.barcode)+":",1),t("div",Zo,o((F=n.value)==null?void 0:F.barcode),1)]),e[70]||(e[70]=t("div",{class:"alert alert-info small"},[t("i",{class:"bi bi-info-circle"}),m(" هذه معاينة لما سيتم طباعته ")],-1))])):p("",!0)]),t("div",ta,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[39]||(e[39]=l=>O.value=!1)},o(i.translations.close),1),(Q=n.value)!=null&&Q.barcode?(r(),M(ot,{key:0,"barcode-data":n.value.barcode,"product-name":n.value.name,translations:i.translations,"printer-settings":a},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0)])])])],6)])]}),_:1},8,["translations"]))}},na=bt(ea,[["__scopeId","data-v-93774393"]]);export{na as default};
