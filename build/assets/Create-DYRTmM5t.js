import{l as y,T as w,m as V,c as x,w as p,o as u,b as s,t as n,a as i,d as b,e as N,i as r,p as c,u as e,f as _,g as h}from"./app-0gpYHNfJ.js";import{_ as U}from"./AuthenticatedLayout-DpP4pYyG.js";import{_ as d}from"./InputError-BmnpY-8-.js";const k={class:"pagetitle"},q={class:"breadcrumb"},P={class:"breadcrumb-item"},S={class:"breadcrumb-item active"},B={class:"breadcrumb-item active"},C={class:"section dashboard"},E={class:"row"},M={class:"col-lg-12"},I={class:"card"},O={class:"card-body"},T={class:"card-title"},D={class:"row mb-3"},L={for:"inputName",class:"col-sm-2 col-form-label"},Q={class:"col-sm-10"},$=["placeholder"],j={class:"row mb-3"},z={for:"inputModel",class:"col-sm-2 col-form-label"},A={class:"col-sm-10"},F=["placeholder"],G={class:"row mb-3"},H={for:"inputOENumber",class:"col-sm-2 col-form-label"},J={class:"col-sm-10"},K=["placeholder"],R={class:"row mb-3"},W={for:"inputSituation",class:"col-sm-2 col-form-label"},X={class:"col-sm-10"},Y=["placeholder"],Z={class:"row mb-3"},ss={for:"inputPrice",class:"col-sm-2 col-form-label"},ts={class:"col-sm-10"},es=["placeholder"],os={class:"row mb-3"},ls={for:"inputPrice",class:"col-sm-2 col-form-label"},as={class:"col-sm-10"},ns=["placeholder"],is={class:"row mb-3"},rs={for:"inputPrice",class:"col-sm-2 col-form-label"},cs={class:"col-sm-10"},ds=["placeholder"],ms={class:"row mb-3"},us={for:"inputQuantity",class:"col-sm-2 col-form-label"},_s={class:"col-sm-10"},hs=["placeholder"],ps={class:"row mb-3"},bs={for:"inputNote",class:"col-sm-2 col-form-label"},fs={class:"col-sm-10"},vs=["placeholder"],gs={class:"row mb-3"},ys={for:"inputdate",class:"col-sm-2 col-form-label"},ws={class:"col-sm-10"},Vs=["placeholder"],xs={class:"row mb-3"},Ns={for:"inputImage",class:"col-sm-2 col-form-label"},Us={class:"col-sm-10"},ks=["value"],qs={class:"text-center"},Ps=["disabled"],Ss={key:0,class:"bi bi-save"},Bs={key:1,class:"spinner-border spinner-border-sm",role:"status","aria-hidden":"true"},Is={__name:"Create",props:{translations:Object},setup(o){const m=y(!1),t=w({name:"",model:"",oe_number:"",situation:"",price_cost:null,price_with_transport:null,selling_price:null,quantity:null,note:"",image:null}),f=()=>{m.value=!0,t.post(route("products.store"),{onSuccess:()=>{m.value=!1},onError:()=>{m.value=!1}})};return(v,l)=>{const g=V("Link");return u(),x(U,{translations:o.translations},{default:p(()=>[s("div",k,[s("h1",null,n(o.translations.products),1),s("nav",null,[s("ol",q,[s("li",P,[i(g,{class:"nav-link",href:v.route("dashboard")},{default:p(()=>[b(n(o.translations.home),1)]),_:1},8,["href"])]),s("li",S,n(o.translations.products),1),s("li",B,n(o.translations.create),1)])])]),s("section",C,[s("div",E,[s("div",M,[s("div",I,[s("div",O,[s("h5",T,n(o.translations.add_new_product),1),s("form",{onSubmit:N(f,["prevent"]),class:"row g-3"},[s("div",D,[s("label",L,n(o.translations.name),1),s("div",Q,[r(s("input",{id:"inputName",type:"text",class:"form-control",placeholder:o.translations.name,"onUpdate:modelValue":l[0]||(l[0]=a=>e(t).name=a)},null,8,$),[[c,e(t).name]]),i(d,{message:e(t).errors.name},null,8,["message"])])]),s("div",j,[s("label",z,n(o.translations.model),1),s("div",A,[r(s("input",{id:"inputModel",type:"text",class:"form-control",placeholder:o.translations.model,"onUpdate:modelValue":l[1]||(l[1]=a=>e(t).model=a)},null,8,F),[[c,e(t).model]]),i(d,{message:e(t).errors.model},null,8,["message"])])]),s("div",G,[s("label",H,n(o.translations.oe_number),1),s("div",J,[r(s("input",{id:"inputOENumber",type:"text",class:"form-control",placeholder:o.translations.oe_number,"onUpdate:modelValue":l[2]||(l[2]=a=>e(t).oe_number=a)},null,8,K),[[c,e(t).oe_number]]),i(d,{message:e(t).errors.oe_number},null,8,["message"])])]),s("div",R,[s("label",W,n(o.translations.situation),1),s("div",X,[r(s("input",{id:"inputSituation",type:"text",class:"form-control",placeholder:o.translations.situation,"onUpdate:modelValue":l[3]||(l[3]=a=>e(t).situation=a)},null,8,Y),[[c,e(t).situation]]),i(d,{message:e(t).errors.situation},null,8,["message"])])]),s("div",Z,[s("label",ss,n(o.translations.price_cost),1),s("div",ts,[r(s("input",{id:"inputPrice",type:"number",class:"form-control",placeholder:o.translations.price_cost,"onUpdate:modelValue":l[4]||(l[4]=a=>e(t).price_cost=a)},null,8,es),[[c,e(t).price_cost]]),i(d,{message:e(t).errors.price_cost},null,8,["message"])])]),s("div",os,[s("label",ls,n(o.translations.price_with_transport),1),s("div",as,[r(s("input",{id:"inputPrice",type:"number",class:"form-control",placeholder:o.translations.price_with_transport,"onUpdate:modelValue":l[5]||(l[5]=a=>e(t).price_with_transport=a)},null,8,ns),[[c,e(t).price_with_transport]]),i(d,{message:e(t).errors.price_with_transport},null,8,["message"])])]),s("div",is,[s("label",rs,n(o.translations.selling_price),1),s("div",cs,[r(s("input",{id:"inputPrice",type:"number",class:"form-control",placeholder:o.translations.selling_price,"onUpdate:modelValue":l[6]||(l[6]=a=>e(t).selling_price=a)},null,8,ds),[[c,e(t).selling_price]]),i(d,{message:e(t).errors.selling_price},null,8,["message"])])]),s("div",ms,[s("label",us,n(o.translations.quantity),1),s("div",_s,[r(s("input",{id:"inputQuantity",type:"number",class:"form-control",placeholder:o.translations.quantity,"onUpdate:modelValue":l[7]||(l[7]=a=>e(t).quantity=a)},null,8,hs),[[c,e(t).quantity]]),i(d,{message:e(t).errors.quantity},null,8,["message"])])]),s("div",ps,[s("label",bs,n(o.translations.note),1),s("div",fs,[r(s("input",{id:"inputNote",type:"text",class:"form-control",placeholder:o.translations.note,"onUpdate:modelValue":l[8]||(l[8]=a=>e(t).note=a)},null,8,vs),[[c,e(t).note]]),i(d,{message:e(t).errors.note},null,8,["message"])])]),s("div",gs,[s("label",ys,n(o.translations.date),1),s("div",ws,[r(s("input",{id:"inputdate",type:"date",class:"form-control",placeholder:o.translations.date,"onUpdate:modelValue":l[9]||(l[9]=a=>e(t).date=a)},null,8,Vs),[[c,e(t).date]]),i(d,{message:e(t).errors.date},null,8,["message"])])]),s("div",xs,[s("label",Ns,n(o.translations.image),1),s("div",Us,[s("input",{id:"inputImage",type:"file",onInput:l[10]||(l[10]=a=>e(t).image=a.target.files[0])},null,32),e(t).progress?(u(),_("progress",{key:0,value:e(t).progress.percentage,max:"100"},n(e(t).progress.percentage)+"% ",9,ks)):h("",!0)])]),s("div",qs,[s("button",{type:"submit",class:"btn btn-primary",disabled:m.value},[b(n(o.translations.save)+" ",1),m.value?h("",!0):(u(),_("i",Ss)),m.value?(u(),_("span",Bs)):h("",!0)],8,Ps)])],32)])])])])])]),_:1},8,["translations"])}}};export{Is as default};