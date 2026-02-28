const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/BarcodePrinter.vue_vue_type_style_index_0_scoped_3a0f17fc_lang-DBbMPVZ4.js","assets/app-JWVPOn2h.js","assets/app-DYyViQcB.css","assets/BarcodePrinter-BeVYShBq.css"])))=>i.map(i=>d[i]);
import{s as ut,r as w,o as mt,a as r,c,b as t,e as p,d as m,t as a,l as d,x as g,v as S,y as $,z as R,h as M,w as Y,j as Z,f as lt,F as nt,n as G,u as it,p as rt,A as tt,g as dt,_ as ct}from"./app-JWVPOn2h.js";import{A as _t}from"./AuthenticatedLayout-DTt4J0BF.js";import{a as Pt}from"./BarcodePrinter.vue_vue_type_style_index_0_scoped_3a0f17fc_lang-DBbMPVZ4.js";import{_ as bt}from"./_plugin-vue_export-helper-DlAUqK2U.js";const St={class:"barcode-printer"},Ct=["disabled"],Bt={key:0,class:"spinner-border spinner-border-sm me-1"},Ut={class:"barcode-settings mt-2"},zt={class:"mb-3"},Vt={class:"text-muted"},Nt={class:"row"},Ht={class:"col-md-4"},Tt={class:"text-muted"},Wt={class:"col-md-4"},Ot={class:"text-muted"},jt={class:"col-md-4"},Gt={class:"text-muted"},Dt={class:"row mt-2"},Mt={class:"col-md-3"},It={class:"form-check"},Lt={class:"col-md-3"},At={class:"form-check"},Jt={class:"col-md-3"},Et={class:"form-check"},Rt={key:0,class:"row mt-3"},qt={class:"col-md-6"},Ft={class:"col-md-6"},Qt={class:"form-check mt-4"},Xt={key:1,class:"row mt-2"},Kt={class:"col-md-6"},Yt={class:"form-check"},Zt={key:2,class:"alert alert-info mt-3"},te={class:"mb-0"},ee={key:0,class:"barcode-preview mt-3 p-3 text-center",style:{background:"white",border:"2px dashed #dee2e6","border-radius":"8px"}},se=["src"],oe={class:"text-muted mt-2 d-block"},ae={__name:"BarcodePrinter",props:{barcodeData:{type:String,required:!0},productName:{type:String,default:""},translations:{type:Object,required:!0},printerSettings:{type:Object,default:()=>({width:2,height:30,type:"PNG"})}},setup(i){const v=ut(),P=i,y=w(!1),I=w(null),T=w(""),h=w(!1),n=w({width:2,height:70,fontSize:10,margin:2,landscape:!0,highQuality:!0,pageWidth:38,pageHeight:26,copies:1,showBarcodeNumber:!0,showPrice:!1,price:0}),L=()=>{h.value=!h.value},W=async()=>{if(!P.barcodeData){v.error("لا يوجد بيانات باركود للطباعة");return}y.value=!0;try{const b=z();await V(b)}catch(b){console.error("Print error:",b),v.error("حدث خطأ أثناء الطباعة")}finally{y.value=!1}},z=()=>{try{const b=document.createElementNS("http://www.w3.org/2000/svg","svg");Pt(b,P.barcodeData,{format:"CODE128",width:n.value.width,height:n.value.height,displayValue:!1,margin:n.value.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const o=new XMLSerializer().serializeToString(b),s=new Blob([o],{type:"image/svg+xml;charset=utf-8"}),f=URL.createObjectURL(s);return"data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(o)))}catch(b){throw console.error("JsBarcode generation error:",b),b}},_=()=>{if(P.barcodeData)try{T.value=z()}catch(b){console.error("Preview update error:",b)}},V=async b=>{try{const o=window.open("","_blank","width=300,height=400"),s=P.productName||"Product",f=P.barcodeData,C=O(b,s,f);o.document.write(C),o.document.close(),v.success("تم إرسال الباركود للطباعة")}catch(o){throw console.error("Direct print error:",o),o}},O=(b,o="Product",s="")=>{const f=n.value.landscape?"landscape":"portrait",C=`${n.value.pageWidth}mm`,U=`${n.value.pageHeight}mm`,A=n.value.landscape?`${C} ${U}`:`${U} ${C}`,K=n.value.landscape?"18mm":"22mm";let q="";for(let J=0;J<n.value.copies;J++)q+=`
  <div class="label-page">
    <div class="label-container">
      <div class="product-name">${o}</div>
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
      size: ${A}; 
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
</html>`};return mt(()=>{P.barcodeData&&_()}),(b,o)=>(r(),c("div",St,[t("button",{class:"btn btn-sm btn-success me-2",onClick:W,disabled:y.value||!i.barcodeData},[y.value?(r(),c("span",Bt)):p("",!0),o[10]||(o[10]=t("i",{class:"bi bi-printer"},null,-1)),m(" "+a(i.translations.print),1)],8,Ct),t("div",Ut,[t("div",zt,[o[11]||(o[11]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-files"}),m(" عدد النسخ")])],-1)),d(t("input",{type:"number",class:"form-control",min:"1",max:"100","onUpdate:modelValue":o[0]||(o[0]=s=>n.value.copies=s),placeholder:"أدخل عدد النسخ المطلوبة"},null,512),[[g,n.value.copies,void 0,{number:!0}]]),t("small",Vt,"سيتم طباعة "+a(n.value.copies)+" نسخة من الباركود",1)]),o[29]||(o[29]=t("hr",null,null,-1)),t("div",Nt,[t("div",Ht,[o[12]||(o[12]=t("label",{class:"form-label"},"عرض الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"1",max:"5",step:"0.1","onUpdate:modelValue":o[1]||(o[1]=s=>n.value.width=s),onInput:_},null,544),[[g,n.value.width]]),t("small",Tt,a(n.value.width),1)]),t("div",Wt,[o[13]||(o[13]=t("label",{class:"form-label"},"ارتفاع الباركود",-1)),d(t("input",{type:"range",class:"form-range",min:"30",max:"150",step:"5","onUpdate:modelValue":o[2]||(o[2]=s=>n.value.height=s),onInput:_},null,544),[[g,n.value.height]]),t("small",Ot,a(n.value.height)+"px",1)]),t("div",jt,[o[14]||(o[14]=t("label",{class:"form-label"},"حجم الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"6",max:"20",step:"1","onUpdate:modelValue":o[3]||(o[3]=s=>n.value.fontSize=s),onInput:_},null,544),[[g,n.value.fontSize]]),t("small",Gt,a(n.value.fontSize)+"px",1)])]),t("div",Dt,[t("div",Mt,[t("div",It,[d(t("input",{class:"form-check-input",type:"checkbox",id:"landscapeMode","onUpdate:modelValue":o[4]||(o[4]=s=>n.value.landscape=s),onChange:_},null,544),[[S,n.value.landscape]]),o[15]||(o[15]=t("label",{class:"form-check-label",for:"landscapeMode"}," طباعة بالعرض (Landscape) ",-1))])]),t("div",Lt,[t("div",At,[d(t("input",{class:"form-check-input",type:"checkbox",id:"highQuality","onUpdate:modelValue":o[5]||(o[5]=s=>n.value.highQuality=s),onChange:_},null,544),[[S,n.value.highQuality]]),o[16]||(o[16]=t("label",{class:"form-check-label",for:"highQuality"}," جودة عالية ",-1))])]),t("div",Jt,[t("div",Et,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showBarcodeNumber","onUpdate:modelValue":o[6]||(o[6]=s=>n.value.showBarcodeNumber=s)},null,512),[[S,n.value.showBarcodeNumber]]),o[17]||(o[17]=t("label",{class:"form-check-label",for:"showBarcodeNumber"}," عرض رقم الباركود ",-1))])]),t("div",{class:"col-md-3"},[t("button",{class:"btn btn-sm btn-info",onClick:L},o[18]||(o[18]=[t("i",{class:"bi bi-info-circle"},null,-1),m(" معلومات الطابعة ")]))])]),n.value.showPrice?(r(),c("div",Rt,[t("div",qt,[o[19]||(o[19]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-currency-dollar"}),m(" سعر البيع")])],-1)),d(t("input",{type:"number",class:"form-control",min:"0",step:"0.01","onUpdate:modelValue":o[7]||(o[7]=s=>n.value.price=s),placeholder:"أدخل سعر البيع",onInput:_},null,544),[[g,n.value.price,void 0,{number:!0}]])]),t("div",Ft,[t("div",Qt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":o[8]||(o[8]=s=>n.value.showPrice=s)},null,512),[[S,n.value.showPrice]]),o[20]||(o[20]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])):p("",!0),n.value.showPrice?p("",!0):(r(),c("div",Xt,[t("div",Kt,[t("div",Yt,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":o[9]||(o[9]=s=>n.value.showPrice=s)},null,512),[[S,n.value.showPrice]]),o[21]||(o[21]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])),h.value?(r(),c("div",Zt,[o[28]||(o[28]=t("h6",null,[t("i",{class:"bi bi-printer"}),m(" معلومات الطابعة الحرارية:")],-1)),t("ul",te,[o[25]||(o[25]=t("li",null,[t("strong",null,"الطابعة:"),m(" Thermal Printer (MHT-L58G)")],-1)),t("li",null,[o[22]||(o[22]=t("strong",null,"حجم الورق المحدد:",-1)),m(" "+a(n.value.pageWidth)+"mm × "+a(n.value.pageHeight)+"mm",1)]),t("li",null,[o[23]||(o[23]=t("strong",null,"الاتجاه:",-1)),m(" "+a(n.value.landscape?"أفقي (Landscape)":"عمودي (Portrait)"),1)]),t("li",null,[o[24]||(o[24]=t("strong",null,"حجم الطباعة:",-1)),m(" "+a(n.value.landscape?`${n.value.pageWidth}mm × ${n.value.pageHeight}mm`:`${n.value.pageHeight}mm × ${n.value.pageWidth}mm`),1)]),o[26]||(o[26]=t("li",null,[t("strong",null,"دقة الطباعة الموصى بها:"),m(" 203 DPI")],-1)),o[27]||(o[27]=t("li",null,"⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة",-1))])])):p("",!0)]),T.value?(r(),c("div",ee,[o[30]||(o[30]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-eye"}),m(" معاينة الباركود")],-1)),t("div",{class:"preview-container",style:$({width:`${n.value.pageWidth*3.78}px`,height:`${n.value.pageHeight*3.78}px`,margin:"0 auto",border:"1px solid #ccc",display:"flex",flexDirection:"column",justifyContent:"center",alignItems:"center",padding:"8px",background:"#fff"})},[t("div",{class:"product-name",style:$({fontSize:`${n.value.fontSize}px`,fontWeight:"bold",textAlign:"center",marginBottom:"4px",maxHeight:"30px",overflow:"hidden",wordWrap:"break-word",width:"100%"})},a(i.productName),5),t("img",{src:T.value,alt:"Barcode Preview",style:$({maxWidth:"90%",height:"auto",maxHeight:n.value.landscape?"68px":"83px",margin:"4px auto"})},null,12,se),n.value.showBarcodeNumber?(r(),c("div",{key:0,class:"barcode-text",style:$({fontSize:`${Math.max(n.value.fontSize-2,4)}px`,fontFamily:"monospace",textAlign:"center",marginTop:"4px",fontWeight:"bold",width:"100%"})},a(i.barcodeData),5)):p("",!0),n.value.showPrice&&n.value.price>0?(r(),c("div",{key:1,class:"price-text",style:$({fontSize:`${Math.max(n.value.fontSize-1,5)}px`,textAlign:"center",marginTop:"4px",fontWeight:"bold",color:"#dc3545",width:"100%"})},a(n.value.price)+" دينار ",5)):p("",!0)],4),t("small",oe,"الحجم الفعلي: "+a(n.value.pageWidth)+"mm × "+a(n.value.pageHeight)+"mm",1)])):p("",!0),t("canvas",{ref_key:"barcodeCanvas",ref:I,style:{display:"none"}},null,512)]))}},et=bt(ae,[["__scopeId","data-v-3a0f17fc"]]),le={class:"section dashboard"},ne={class:"row"},ie={class:"col-12"},re={class:"card"},de={class:"card-body"},ce={class:"row"},ue={class:"col-md-4"},me=["placeholder"],be={class:"col-md-2"},he={type:"submit",class:"btn btn-primary"},pe={class:"col-md-3"},ve=["disabled"],ge={class:"col-md-3"},fe={class:"col-12"},ye={class:"card"},we={class:"card-body"},xe={class:"table-responsive"},ke={class:"table text-center"},$e=["checked"],_e=["value"],Pe={key:0,class:"badge bg-success"},Se={key:1,class:"badge bg-warning"},Ce={class:"btn-group",role:"group"},Be=["onClick","disabled"],Ue=["onClick"],ze=["onClick"],Ve={class:"d-flex justify-content-center mt-3"},Ne={class:"pagination"},He={key:1,class:"page-link"},Te={key:1,class:"page-link"},We={class:"modal-dialog"},Oe={class:"modal-content"},je={class:"modal-header"},Ge={class:"modal-title"},De={class:"modal-body"},Me={class:"mb-3"},Ie={class:"form-label"},Le=["value"],Ae={class:"mb-3"},Je={class:"form-label"},Ee={class:"row"},Re={class:"col-md-6"},qe={class:"mb-3"},Fe={class:"form-label"},Qe={class:"col-md-6"},Xe={class:"mb-3"},Ke={class:"form-label"},Ye={class:"modal-footer"},Ze=["disabled"],ts={key:0,class:"spinner-border spinner-border-sm me-2"},es={class:"modal-dialog"},ss={class:"modal-content"},os={class:"modal-header"},as={class:"modal-title"},ls={class:"modal-body"},ns={class:"mb-3"},is={class:"form-label"},rs=["value"],ds={class:"mb-3"},cs={class:"form-label"},us=["value"],ms={class:"mb-3"},bs={class:"form-label"},hs={class:"mb-3"},ps={class:"form-label"},vs={class:"row"},gs={class:"col-md-6"},fs={class:"mb-3"},ys={class:"form-label"},ws={class:"col-md-6"},xs={class:"mb-3"},ks={class:"form-label"},$s={class:"modal-footer"},_s={class:"modal-dialog modal-lg"},Ps={class:"modal-content"},Ss={class:"modal-header"},Cs={class:"modal-title"},Bs={class:"modal-body"},Us={class:"mb-3"},zs={class:"form-label"},Vs={class:"border p-2",style:{"max-height":"200px","overflow-y":"auto"}},Ns={class:"mb-3"},Hs={class:"form-label"},Ts={key:0,class:"barcode-preview mb-3 p-3 text-center",style:{background:"white",border:"2px dashed #dee2e6","border-radius":"8px"}},Ws=["src"],Os={class:"text-muted mt-2 d-block"},js={class:"barcode-settings mt-3 p-3",style:{background:"#f8f9fa","border-radius":"8px",border:"1px solid #dee2e6"}},Gs={class:"row mb-2"},Ds={class:"col-md-6"},Ms={class:"col-md-6"},Is={class:"mb-3"},Ls={class:"btn-group btn-group-sm mt-1",role:"group"},As={class:"row"},Js={class:"col-md-4"},Es={class:"text-muted"},Rs={class:"col-md-4"},qs={class:"text-muted"},Fs={class:"col-md-4"},Qs={class:"text-muted"},Xs={class:"row mt-3"},Ks={class:"col-md-4"},Ys={class:"form-check"},Zs={class:"col-md-4"},to={class:"form-check"},eo={class:"col-md-4"},so={class:"form-check"},oo={key:0,class:"row mt-3"},ao={class:"col-md-6"},lo={class:"col-md-6"},no={class:"form-check mt-4"},io={key:1,class:"row mt-2"},ro={class:"col-md-6"},co={class:"form-check"},uo={class:"alert alert-info mt-3 mb-0",style:{"font-size":"0.9em"}},mo={class:"mb-0 mt-2",style:{"font-size":"0.85em"}},bo={class:"modal-footer"},ho=["disabled"],po={key:0,class:"spinner-border spinner-border-sm me-2"},vo={class:"modal-dialog"},go={class:"modal-content"},fo={class:"modal-header"},yo={class:"modal-title"},wo={class:"modal-body"},xo={class:"alert alert-info"},ko={class:"mb-3"},$o={class:"form-label"},_o={class:"row"},Po={class:"col-md-6"},So={class:"mb-3"},Co={class:"form-label"},Bo={class:"col-md-6"},Uo={class:"mb-3"},zo={class:"form-label"},Vo={class:"modal-footer"},No={class:"modal-dialog modal-lg"},Ho={class:"modal-content"},To={class:"modal-header"},Wo={class:"modal-title"},Oo={class:"modal-body text-center"},jo={key:0,class:"text-center"},Go={class:"spinner-border",role:"status"},Do={class:"visually-hidden"},Mo={key:1,class:"border p-4",style:{"max-width":"400px",margin:"0 auto"}},Io={class:"mb-3"},Lo={class:"fw-bold text-center"},Ao={class:"mb-3"},Jo=["src","alt"],Eo={class:"mb-3"},Ro={class:"text-muted small"},qo={class:"fw-bold font-monospace fs-5"},Fo={class:"modal-footer"},Qo={__name:"Index",props:{products:Object,translations:Object,filters:Object},setup(i){var at;const v=ut(),P=i,y=w(!1),I=w(!1),T=R({search:((at=P.filters)==null?void 0:at.search)||""}),h=w([]),n=w(null),L=w(""),W=w(!1),z=w(!1),_=w(!1),V=w(!1),O=w(!1),b=R({type:"PNG",width:2,height:30}),o=R({type:"PNG",width:2,height:30}),s=R({type:"SVG",width:2,height:70,fontSize:10,margin:2,landscape:!0,highQuality:!0,pageWidth:38,pageHeight:26,copies:1,showBarcodeNumber:!0,showPrice:!1,price:0}),f=R({type:"PNG",width:2,height:30}),C=w(1),U=w(1),A=w(""),K=()=>{dt.get(route("barcode.index"),T,{preserveState:!0,replace:!0})},q=()=>{h.value.length===P.products.data.length?h.value=[]:h.value=P.products.data.map(u=>u.id)},J=u=>{const e=P.products.data.find(x=>x.id===u);return e?e.name:""},st=u=>P.products.data.find(e=>e.id===u),ht=()=>{_.value=!0,E()},E=async()=>{if(h.value.length===0)return;const u=st(h.value[0]);if(!(!u||!u.barcode))try{const e=(await ct(async()=>{const{default:j}=await import("./BarcodePrinter.vue_vue_type_style_index_0_scoped_3a0f17fc_lang-DBbMPVZ4.js").then(D=>D.J);return{default:j}},__vite__mapDeps([0,1,2,3]))).default,x=document.createElementNS("http://www.w3.org/2000/svg","svg");e(x,u.barcode,{format:"CODE128",width:s.width,height:s.height,displayValue:!1,margin:s.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const B=new XMLSerializer().serializeToString(x);L.value="data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(B)))}catch(e){console.error("Batch preview generation error:",e)}},pt=async u=>{y.value=!0;try{const x=await(await fetch(route("barcode.generate"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:u.id,type:"PNG",width:2,height:30})})).json();x.success?(v.success("تم توليد الباركود بنجاح"),window.location.reload()):v.error(x.message||"حدث خطأ أثناء توليد الباركود")}catch(e){console.error("Generate barcode error:",e),v.error("حدث خطأ أثناء توليد الباركود")}finally{y.value=!1}},ot=async()=>{y.value=!0;try{const e=await(await fetch(route("barcode.generate"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:n.value.id,type:b.type,width:b.width,height:b.height})})).json();e.success?(v.success(e.message),W.value=!1,dt.reload()):v.error(e.message)}catch{v.error("حدث خطأ أثناء توليد الباركود")}finally{y.value=!1}},vt=async()=>{y.value=!0;try{const e=await(await fetch(route("barcode.print"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_id:n.value.id,quantity:C.value,printer_settings:{type:o.type,width:o.width,height:o.height}})})).json();e.success?(v.success(e.message),z.value=!1):v.error(e.message)}catch{v.error("حدث خطأ أثناء الطباعة")}finally{y.value=!1}},gt=async()=>{y.value=!0;try{const e=await(await fetch(route("barcode.batch.print"),{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({product_ids:h.value,quantity_per_product:U.value,printer_settings:{type:s.type,width:s.width,height:s.height}})})).json();e.success?(v.success(e.message),await ft(e.results),_.value=!1,h.value=[]):v.error(e.message)}catch{v.error("حدث خطأ أثناء الطباعة المجمعة")}finally{y.value=!1}},ft=async u=>{try{const e=(await ct(async()=>{const{default:k}=await import("./BarcodePrinter.vue_vue_type_style_index_0_scoped_3a0f17fc_lang-DBbMPVZ4.js").then(X=>X.J);return{default:k}},__vite__mapDeps([0,1,2,3]))).default,x=[];for(const k of u)if(k.success&&k.product){const X=document.createElementNS("http://www.w3.org/2000/svg","svg");e(X,k.product.barcode,{format:"CODE128",width:s.width,height:s.height,displayValue:!1,margin:s.margin,background:"#ffffff",lineColor:"#000000",xmlDocument:document});const kt=new XMLSerializer().serializeToString(X),$t="data:image/svg+xml;base64,"+btoa(unescape(encodeURIComponent(kt)));x.push({...k,svgUrl:$t})}const B=window.open("","_blank","width=400,height=600");if(!B){v.error("فشل فتح نافذة الطباعة. يرجى السماح بالنوافذ المنبثقة.");return}let j="";for(const k of x)j+=`
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
    `;B.document.write(l),B.document.close()}catch(e){console.error("Batch print error:",e),v.error("حدث خطأ أثناء طباعة الباركودات")}},yt=async u=>{n.value=u,I.value=!0,O.value=!0;try{const e=new URLSearchParams({code:u.barcode,type:f.type,width:f.width,height:f.height}),x=await fetch(`${route("barcode.preview")}?${e}`);if(x.ok){const B=await x.blob();A.value=URL.createObjectURL(B)}else v.error("فشل في تحميل معاينة الباركود")}catch{v.error("حدث خطأ أثناء تحميل المعاينة")}finally{I.value=!1}},wt=u=>{const e=new URLSearchParams({type:f.type,width:f.width,height:f.height});window.open(`${route("barcode.download",u.id)}?${e}`,"_blank")},xt=()=>{localStorage.setItem("printerSettings",JSON.stringify(f)),v.success("تم حفظ إعدادات الطابعة"),V.value=!1};return mt(()=>{const u=localStorage.getItem("printerSettings");if(u){const e=JSON.parse(u);Object.assign(f,e),Object.assign(b,e),Object.assign(o,e),Object.assign(s,e)}}),(u,e)=>(r(),M(_t,{translations:i.translations},{default:Y(()=>{var x,B,j,D,N,H,F,Q;return[t("section",le,[t("div",ne,[t("div",ie,[t("div",re,[t("div",de,[t("form",{onSubmit:Z(K,["prevent"])},[t("div",ce,[t("div",ue,[d(t("input",{type:"text",class:"form-control","onUpdate:modelValue":e[0]||(e[0]=l=>T.search=l),placeholder:i.translations.search},null,8,me),[[g,T.search]])]),t("div",be,[t("button",he,[m(a(i.translations.search)+" ",1),e[40]||(e[40]=t("i",{class:"bi bi-search"},null,-1))])]),t("div",pe,[t("button",{type:"button",class:"btn btn-success",onClick:ht,disabled:h.value.length===0},a(i.translations.batch_print)+" ("+a(h.value.length)+") ",9,ve)]),t("div",ge,[t("button",{type:"button",class:"btn btn-info",onClick:e[1]||(e[1]=l=>V.value=!0)},a(i.translations.printer_settings),1)])])],32)])])]),t("div",fe,[t("div",ye,[t("div",we,[t("div",xe,[t("table",ke,[t("thead",null,[t("tr",null,[t("th",null,[t("input",{type:"checkbox",onChange:q,checked:h.value.length===i.products.data.length&&i.products.data.length>0},null,40,$e)]),t("th",null,a(i.translations.name),1),t("th",null,a(i.translations.model),1),t("th",null,a(i.translations.barcode),1),t("th",null,a(i.translations.quantity),1),t("th",null,a(i.translations.price),1),t("th",null,a(i.translations.actions),1)])]),t("tbody",null,[(r(!0),c(nt,null,lt(i.products.data,l=>(r(),c("tr",{key:l.id},[t("td",null,[d(t("input",{type:"checkbox",value:l.id,"onUpdate:modelValue":e[2]||(e[2]=k=>h.value=k)},null,8,_e),[[S,h.value]])]),t("td",null,a(l.name),1),t("td",null,a(l.model),1),t("td",null,[l.barcode?(r(),c("span",Pe,a(l.barcode),1)):(r(),c("span",Se,a(i.translations.no_barcode),1))]),t("td",null,a(l.quantity),1),t("td",null,a(l.price)+" "+a(i.translations.dinar),1),t("td",null,[t("div",Ce,[l.barcode?p("",!0):(r(),c("button",{key:0,class:"btn btn-sm btn-primary",onClick:k=>pt(l),disabled:y.value},[e[41]||(e[41]=t("i",{class:"bi bi-qr-code"},null,-1)),m(" "+a(i.translations.generate),1)],8,Be)),l.barcode?(r(),M(et,{key:1,"barcode-data":l.barcode,"product-name":l.name,translations:i.translations,"printer-settings":o},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0),l.barcode?(r(),c("button",{key:2,class:"btn btn-sm btn-info",onClick:k=>yt(l)},[e[42]||(e[42]=t("i",{class:"bi bi-eye"},null,-1)),m(" "+a(i.translations.preview),1)],8,Ue)):p("",!0),l.barcode?(r(),c("button",{key:3,class:"btn btn-sm btn-secondary",onClick:k=>wt(l)},[e[43]||(e[43]=t("i",{class:"bi bi-download"},null,-1)),m(" "+a(i.translations.download),1)],8,ze)):p("",!0)])])]))),128))])])]),t("div",Ve,[t("nav",null,[t("ul",Ne,[t("li",{class:G(["page-item",{disabled:!i.products.prev_page_url}])},[i.products.prev_page_url?(r(),M(it(rt),{key:0,class:"page-link",href:i.products.prev_page_url},{default:Y(()=>[m(a(i.translations.previous),1)]),_:1},8,["href"])):(r(),c("span",He,a(i.translations.previous),1))],2),t("li",{class:G(["page-item",{disabled:!i.products.next_page_url}])},[i.products.next_page_url?(r(),M(it(rt),{key:0,class:"page-link",href:i.products.next_page_url},{default:Y(()=>[m(a(i.translations.next),1)]),_:1},8,["href"])):(r(),c("span",Te,a(i.translations.next),1))],2)])])])])])])]),t("div",{class:G(["modal fade",{show:W.value}]),style:$({display:W.value?"block":"none"})},[t("div",We,[t("div",Oe,[t("div",je,[t("h5",Ge,a(i.translations.generate_barcode),1),t("button",{type:"button",class:"btn-close",onClick:e[3]||(e[3]=l=>W.value=!1)})]),t("div",De,[t("form",{onSubmit:Z(ot,["prevent"])},[t("div",Me,[t("label",Ie,a(i.translations.product_name),1),t("input",{type:"text",class:"form-control",value:(x=n.value)==null?void 0:x.name,readonly:""},null,8,Le)]),t("div",Ae,[t("label",Je,a(i.translations.barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[4]||(e[4]=l=>b.type=l)},e[44]||(e[44]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[tt,b.type]])]),t("div",Ee,[t("div",Re,[t("div",qe,[t("label",Fe,a(i.translations.width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[5]||(e[5]=l=>b.width=l),min:"1",max:"10"},null,512),[[g,b.width]])])]),t("div",Qe,[t("div",Xe,[t("label",Ke,a(i.translations.height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[6]||(e[6]=l=>b.height=l),min:"10",max:"200"},null,512),[[g,b.height]])])])])],32)]),t("div",Ye,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[7]||(e[7]=l=>W.value=!1)},a(i.translations.cancel),1),t("button",{type:"button",class:"btn btn-primary",onClick:ot,disabled:y.value},[y.value?(r(),c("span",ts)):p("",!0),m(" "+a(i.translations.generate),1)],8,Ze)])])])],6),t("div",{class:G(["modal fade",{show:z.value}]),style:$({display:z.value?"block":"none"})},[t("div",es,[t("div",ss,[t("div",os,[t("h5",as,a(i.translations.print_barcode),1),t("button",{type:"button",class:"btn-close",onClick:e[8]||(e[8]=l=>z.value=!1)})]),t("div",ls,[t("form",{onSubmit:Z(vt,["prevent"])},[t("div",ns,[t("label",is,a(i.translations.product_name),1),t("input",{type:"text",class:"form-control",value:(B=n.value)==null?void 0:B.name,readonly:""},null,8,rs)]),t("div",ds,[t("label",cs,a(i.translations.barcode),1),t("input",{type:"text",class:"form-control",value:(j=n.value)==null?void 0:j.barcode,readonly:""},null,8,us)]),t("div",ms,[t("label",bs,a(i.translations.print_quantity),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[9]||(e[9]=l=>C.value=l),min:"1",max:"100",value:"1"},null,512),[[g,C.value]])]),t("div",hs,[t("label",ps,a(i.translations.barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[10]||(e[10]=l=>o.type=l)},e[45]||(e[45]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[tt,o.type]])]),t("div",vs,[t("div",gs,[t("div",fs,[t("label",ys,a(i.translations.width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[11]||(e[11]=l=>o.width=l),min:"1",max:"10"},null,512),[[g,o.width]])])]),t("div",ws,[t("div",xs,[t("label",ks,a(i.translations.height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[12]||(e[12]=l=>o.height=l),min:"10",max:"200"},null,512),[[g,o.height]])])])])],32)]),t("div",$s,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[13]||(e[13]=l=>z.value=!1)},a(i.translations.cancel),1),(D=n.value)!=null&&D.barcode?(r(),M(et,{key:0,"barcode-data":n.value.barcode,"product-name":n.value.name,translations:i.translations,"printer-settings":o},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0)])])])],6),t("div",{class:G(["modal fade",{show:_.value}]),style:$({display:_.value?"block":"none"})},[t("div",_s,[t("div",Ps,[t("div",Ss,[t("h5",Cs,a(i.translations.batch_print),1),t("button",{type:"button",class:"btn-close",onClick:e[14]||(e[14]=l=>_.value=!1)})]),t("div",Bs,[t("div",Us,[t("label",zs,a(i.translations.selected_products)+" ("+a(h.value.length)+")",1),t("div",Vs,[(r(!0),c(nt,null,lt(h.value,l=>(r(),c("div",{key:l,class:"mb-1"},a(J(l)),1))),128))])]),t("div",Ns,[t("label",Hs,a(i.translations.quantity_per_product),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[15]||(e[15]=l=>U.value=l),min:"1",max:"10",value:"1"},null,512),[[g,U.value]])]),h.value.length>0?(r(),c("div",Ts,[e[46]||(e[46]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-eye"}),m(" معاينة الباركود (أول منتج)")],-1)),t("div",{class:"preview-container",style:$({width:`${s.pageWidth*3.78}px`,height:`${s.pageHeight*3.78}px`,margin:"0 auto",border:"1px solid #ccc",display:"flex",flexDirection:"column",justifyContent:"center",alignItems:"center",padding:"8px",background:"#fff"})},[t("div",{class:"product-name",style:$({fontSize:`${s.fontSize}px`,fontWeight:"bold",textAlign:"center",marginBottom:"4px",maxHeight:"30px",overflow:"hidden",wordWrap:"break-word",width:"100%"})},a(J(h.value[0])),5),L.value?(r(),c("img",{key:0,src:L.value,alt:"Barcode Preview",style:$({maxWidth:"90%",height:"auto",maxHeight:s.landscape?"68px":"83px",margin:"4px auto"})},null,12,Ws)):p("",!0),s.showBarcodeNumber?(r(),c("div",{key:1,class:"barcode-text",style:$({fontSize:`${Math.max(s.fontSize-2,4)}px`,fontFamily:"monospace",textAlign:"center",marginTop:"4px",fontWeight:"bold",width:"100%"})},a((N=st(h.value[0]))==null?void 0:N.barcode),5)):p("",!0),s.showPrice&&s.price>0?(r(),c("div",{key:2,class:"price-text",style:$({fontSize:`${Math.max(s.fontSize-1,5)}px`,textAlign:"center",marginTop:"4px",fontWeight:"bold",color:"#dc3545",width:"100%"})},a(s.price)+" دينار ",5)):p("",!0)],4),t("small",Os,"الحجم الفعلي: "+a(s.pageWidth)+"mm × "+a(s.pageHeight)+"mm",1)])):p("",!0),t("div",js,[e[65]||(e[65]=t("h6",{class:"mb-3"},[t("i",{class:"bi bi-sliders"}),m(" إعدادات الباركود")],-1)),t("div",Gs,[t("div",Ds,[e[47]||(e[47]=t("label",{class:"form-label"},[t("strong",null,"📏 عرض الورق (mm)")],-1)),d(t("input",{type:"number",class:"form-control form-control-sm",min:"20",max:"80",step:"1","onUpdate:modelValue":e[16]||(e[16]=l=>s.pageWidth=l)},null,512),[[g,s.pageWidth,void 0,{number:!0}]])]),t("div",Ms,[e[48]||(e[48]=t("label",{class:"form-label"},[t("strong",null,"📏 ارتفاع الورق (mm)")],-1)),d(t("input",{type:"number",class:"form-control form-control-sm",min:"20",max:"80",step:"1","onUpdate:modelValue":e[17]||(e[17]=l=>s.pageHeight=l)},null,512),[[g,s.pageHeight,void 0,{number:!0}]])])]),t("div",Is,[e[50]||(e[50]=t("small",{class:"text-muted"},"أحجام شائعة:",-1)),t("div",Ls,[t("button",{type:"button",class:"btn btn-outline-primary",onClick:e[18]||(e[18]=l=>{s.pageWidth=38,s.pageHeight=26})},e[49]||(e[49]=[t("strong",null,"38×26",-1)])),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[19]||(e[19]=l=>{s.pageWidth=35,s.pageHeight=28})}," 35×28 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[20]||(e[20]=l=>{s.pageWidth=38,s.pageHeight=28})}," 38×28 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[21]||(e[21]=l=>{s.pageWidth=40,s.pageHeight=30})}," 40×30 "),t("button",{type:"button",class:"btn btn-outline-secondary",onClick:e[22]||(e[22]=l=>{s.pageWidth=50,s.pageHeight=30})}," 50×30 ")])]),e[66]||(e[66]=t("hr",null,null,-1)),t("div",As,[t("div",Js,[e[51]||(e[51]=t("label",{class:"form-label"},"عرض الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"1",max:"5",step:"0.1","onUpdate:modelValue":e[23]||(e[23]=l=>s.width=l),onInput:E},null,544),[[g,s.width]]),t("small",Es,a(s.width),1)]),t("div",Rs,[e[52]||(e[52]=t("label",{class:"form-label"},"ارتفاع الباركود",-1)),d(t("input",{type:"range",class:"form-range",min:"30",max:"150",step:"5","onUpdate:modelValue":e[24]||(e[24]=l=>s.height=l),onInput:E},null,544),[[g,s.height]]),t("small",qs,a(s.height)+"px",1)]),t("div",Fs,[e[53]||(e[53]=t("label",{class:"form-label"},"حجم الخط",-1)),d(t("input",{type:"range",class:"form-range",min:"6",max:"20",step:"1","onUpdate:modelValue":e[25]||(e[25]=l=>s.fontSize=l),onInput:E},null,544),[[g,s.fontSize]]),t("small",Qs,a(s.fontSize)+"px",1)])]),t("div",Xs,[t("div",Ks,[t("div",Ys,[d(t("input",{class:"form-check-input",type:"checkbox",id:"batchLandscapeMode","onUpdate:modelValue":e[26]||(e[26]=l=>s.landscape=l)},null,512),[[S,s.landscape]]),e[54]||(e[54]=t("label",{class:"form-check-label",for:"batchLandscapeMode"}," طباعة بالعرض (Landscape) ",-1))])]),t("div",Zs,[t("div",to,[d(t("input",{class:"form-check-input",type:"checkbox",id:"batchHighQuality","onUpdate:modelValue":e[27]||(e[27]=l=>s.highQuality=l)},null,512),[[S,s.highQuality]]),e[55]||(e[55]=t("label",{class:"form-check-label",for:"batchHighQuality"}," جودة عالية (SVG) ",-1))])]),t("div",eo,[t("div",so,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showBarcodeNumber","onUpdate:modelValue":e[28]||(e[28]=l=>s.showBarcodeNumber=l)},null,512),[[S,s.showBarcodeNumber]]),e[56]||(e[56]=t("label",{class:"form-check-label",for:"showBarcodeNumber"}," عرض رقم الباركود ",-1))])])]),s.showPrice?(r(),c("div",oo,[t("div",ao,[e[57]||(e[57]=t("label",{class:"form-label"},[t("strong",null,[t("i",{class:"bi bi-currency-dollar"}),m(" سعر البيع")])],-1)),d(t("input",{type:"number",class:"form-control",min:"0",step:"0.01","onUpdate:modelValue":e[29]||(e[29]=l=>s.price=l),placeholder:"أدخل سعر البيع",onInput:E},null,544),[[g,s.price,void 0,{number:!0}]])]),t("div",lo,[t("div",no,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":e[30]||(e[30]=l=>s.showPrice=l)},null,512),[[S,s.showPrice]]),e[58]||(e[58]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])):p("",!0),s.showPrice?p("",!0):(r(),c("div",io,[t("div",ro,[t("div",co,[d(t("input",{class:"form-check-input",type:"checkbox",id:"showPrice","onUpdate:modelValue":e[31]||(e[31]=l=>s.showPrice=l)},null,512),[[S,s.showPrice]]),e[59]||(e[59]=t("label",{class:"form-check-label",for:"showPrice"}," عرض سعر البيع ",-1))])])])),t("div",uo,[e[64]||(e[64]=t("strong",null,[t("i",{class:"bi bi-info-circle"}),m(" للطابعة الحرارية MHT-L58G:")],-1)),t("ul",mo,[t("li",null,[e[60]||(e[60]=t("strong",null,"حجم الورق المحدد:",-1)),m(" "+a(s.pageWidth)+"mm × "+a(s.pageHeight)+"mm",1)]),t("li",null,[e[61]||(e[61]=t("strong",null,"اتجاه:",-1)),m(" "+a(s.landscape?"أفقي (Landscape)":"عمودي (Portrait)"),1)]),t("li",null,[e[62]||(e[62]=t("strong",null,"حجم الطباعة:",-1)),m(" "+a(s.landscape?`${s.pageWidth}mm × ${s.pageHeight}mm`:`${s.pageHeight}mm × ${s.pageWidth}mm`),1)]),e[63]||(e[63]=t("li",null,"⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة",-1))])])])]),t("div",bo,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[32]||(e[32]=l=>_.value=!1)},a(i.translations.cancel),1),t("button",{type:"button",class:"btn btn-success",onClick:gt,disabled:y.value},[y.value?(r(),c("span",po)):p("",!0),e[67]||(e[67]=t("i",{class:"bi bi-printer"},null,-1)),m(" "+a(i.translations.print_all)+" ("+a(h.value.length*U.value)+") ",1)],8,ho)])])])],6),t("div",{class:G(["modal fade",{show:V.value}]),style:$({display:V.value?"block":"none"})},[t("div",vo,[t("div",go,[t("div",fo,[t("h5",yo,a(i.translations.printer_settings),1),t("button",{type:"button",class:"btn-close",onClick:e[33]||(e[33]=l=>V.value=!1)})]),t("div",wo,[t("div",xo,[e[68]||(e[68]=t("i",{class:"bi bi-info-circle"},null,-1)),m(" "+a(i.translations.printer_settings_info),1)]),t("div",ko,[t("label",$o,a(i.translations.default_barcode_type),1),d(t("select",{class:"form-select","onUpdate:modelValue":e[34]||(e[34]=l=>f.type=l)},e[69]||(e[69]=[t("option",{value:"PNG"},"PNG",-1),t("option",{value:"SVG"},"SVG",-1),t("option",{value:"JPG"},"JPG",-1)]),512),[[tt,f.type]])]),t("div",_o,[t("div",Po,[t("div",So,[t("label",Co,a(i.translations.default_width),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[35]||(e[35]=l=>f.width=l),min:"1",max:"10"},null,512),[[g,f.width]])])]),t("div",Bo,[t("div",Uo,[t("label",zo,a(i.translations.default_height),1),d(t("input",{type:"number",class:"form-control","onUpdate:modelValue":e[36]||(e[36]=l=>f.height=l),min:"10",max:"200"},null,512),[[g,f.height]])])])])]),t("div",Vo,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[37]||(e[37]=l=>V.value=!1)},a(i.translations.cancel),1),t("button",{type:"button",class:"btn btn-primary",onClick:xt},a(i.translations.save),1)])])])],6),t("div",{class:G(["modal fade",{show:O.value}]),style:$({display:O.value?"block":"none"})},[t("div",No,[t("div",Ho,[t("div",To,[t("h5",Wo,a(i.translations.barcode_preview),1),t("button",{type:"button",class:"btn-close",onClick:e[38]||(e[38]=l=>O.value=!1)})]),t("div",Oo,[I.value?(r(),c("div",jo,[t("div",Go,[t("span",Do,a(i.translations.loading),1)])])):A.value?(r(),c("div",Mo,[t("div",Io,[t("h4",Lo,a(((H=n.value)==null?void 0:H.name)||"Product"),1)]),t("div",Ao,[t("img",{src:A.value,alt:i.translations.barcode_preview,class:"img-fluid",style:{width:"100%",padding:"10px",background:"white"}},null,8,Jo)]),t("div",Eo,[t("div",Ro,a(i.translations.barcode)+":",1),t("div",qo,a((F=n.value)==null?void 0:F.barcode),1)]),e[70]||(e[70]=t("div",{class:"alert alert-info small"},[t("i",{class:"bi bi-info-circle"}),m(" هذه معاينة لما سيتم طباعته ")],-1))])):p("",!0)]),t("div",Fo,[t("button",{type:"button",class:"btn btn-secondary",onClick:e[39]||(e[39]=l=>O.value=!1)},a(i.translations.close),1),(Q=n.value)!=null&&Q.barcode?(r(),M(et,{key:0,"barcode-data":n.value.barcode,"product-name":n.value.name,translations:i.translations,"printer-settings":o},null,8,["barcode-data","product-name","translations","printer-settings"])):p("",!0)])])])],6)])]}),_:1},8,["translations"]))}},ta=bt(Qo,[["__scopeId","data-v-14f7658e"]]);export{ta as default};
