import{l as w,T as x,m as k,c as V,w as f,o as d,b as s,t as a,a as i,d as v,e as N,i as m,p as u,u as o,f as _,g as b}from"./app-DIMJ05Jz.js";import{_ as B}from"./AuthenticatedLayout-B-WPr0_P.js";import{_ as h}from"./InputError-CESANGbJ.js";const S={class:"pagetitle"},T={class:"breadcrumb"},U={class:"breadcrumb-item"},A={class:"breadcrumb-item active"},C={class:"breadcrumb-item active"},E={class:"section dashboard"},O={class:"row"},j={class:"col-lg-12"},D={class:"card"},L={class:"card-body"},M={class:"card-title"},P={class:"row mb-3"},$={for:"inputName",class:"col-sm-2 col-form-label"},H={class:"col-sm-10"},I=["placeholder"],q={class:"row mb-3"},z={for:"inputPhone",class:"col-sm-2 col-form-label"},F={class:"col-sm-10"},G=["placeholder"],J={class:"row mb-3"},K={for:"inputAddress",class:"col-sm-2 col-form-label"},Q={class:"col-sm-10"},R=["placeholder"],W={class:"row mb-3"},X={for:"inputNotes",class:"col-sm-2 col-form-label"},Y={class:"col-sm-10"},Z=["placeholder"],ss={class:"row mb-3"},es={for:"inputAvatar",class:"col-sm-2 col-form-label"},ts={class:"col-sm-10"},os=["value"],as={class:"text-center"},ls=["disabled"],ns={key:0,class:"bi bi-save"},rs={key:1,class:"spinner-border spinner-border-sm",role:"status","aria-hidden":"true"},ms={__name:"Edit",props:{customer:Object,roles:Object,translations:Array},setup(e){const c=e,r=w(!1),t=x({avatar:null,name:c.customer.name,phone:c.customer.phone,address:c.customer.address,notes:c.customer.notes}),p=()=>{r.value=!0,t.post(route("customers.update",{customer:c.customer.id}),{onSuccess:()=>{r.value=!1},onError:()=>{r.value=!1}})};return(g,l)=>{const y=k("Link");return d(),V(B,{translations:e.translations},{default:f(()=>[s("div",S,[s("h1",null,a(e.translations.customers),1),s("nav",null,[s("ol",T,[s("li",U,[i(y,{class:"nav-link",href:g.route("dashboard")},{default:f(()=>[v(a(e.translations.Home),1)]),_:1},8,["href"])]),s("li",A,a(e.translations.customers),1),s("li",C,a(e.translations.edit),1)])])]),s("section",E,[s("div",O,[s("div",j,[s("div",D,[s("div",L,[s("h5",M,a(e.translations.edit_customer_info),1),s("form",{onSubmit:N(p,["prevent"]),class:"row g-3",method:"POST"},[s("div",P,[s("label",$,a(e.translations.name),1),s("div",H,[m(s("input",{type:"text",class:"form-control",placeholder:e.translations.name,"onUpdate:modelValue":l[0]||(l[0]=n=>o(t).name=n)},null,8,I),[[u,o(t).name]]),i(h,{message:o(t).errors.name},null,8,["message"])])]),s("div",q,[s("label",z,a(e.translations.phone),1),s("div",F,[m(s("input",{type:"text",class:"form-control",placeholder:e.translations.phone,"onUpdate:modelValue":l[1]||(l[1]=n=>o(t).phone=n)},null,8,G),[[u,o(t).phone]]),i(h,{message:o(t).errors.phone},null,8,["message"])])]),s("div",J,[s("label",K,a(e.translations.address),1),s("div",Q,[m(s("input",{type:"text",class:"form-control",placeholder:e.translations.address,"onUpdate:modelValue":l[2]||(l[2]=n=>o(t).address=n)},null,8,R),[[u,o(t).address]]),i(h,{message:o(t).errors.address},null,8,["message"])])]),s("div",W,[s("label",X,a(e.translations.notes),1),s("div",Y,[m(s("input",{type:"text",class:"form-control",placeholder:e.translations.notes,"onUpdate:modelValue":l[3]||(l[3]=n=>o(t).notes=n)},null,8,Z),[[u,o(t).notes]]),i(h,{message:o(t).errors.notes},null,8,["message"])])]),s("div",ss,[s("label",es,a(e.translations.image),1),s("div",ts,[s("input",{type:"file",onInput:l[4]||(l[4]=n=>o(t).avatar=n.target.files[0])},null,32),o(t).progress?(d(),_("progress",{key:0,value:o(t).progress.percentage,max:"100"},a(o(t).progress.percentage)+"% ",9,os)):b("",!0)])]),s("div",as,[s("button",{type:"submit",class:"btn btn-primary",disabled:r.value},[v(a(e.translations.save)+"   ",1),r.value?b("",!0):(d(),_("i",ns)),r.value?(d(),_("span",rs)):b("",!0)],8,ls)])],32)])])])])])]),_:1},8,["translations"])}}};export{ms as default};