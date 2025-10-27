/* Helpers */
function $(q){return document.querySelector(q)}
function $all(q){return Array.from(document.querySelectorAll(q))}

/* Mostrar/ocultar objetos de PARTIAL */
function toggleScope(){
    const type=$("#type"), scope=$("#scopeBox");
    if(!type||!scope) return;
    scope.hidden = String(type.value||"").toUpperCase() !== "PARTIAL";
}

/* Accesibilidad focus-visible */
function enableFocusVisible(){
    function onKey(e){ if(e.key==="Tab") document.body.classList.add("using-kb"); }
    function onMouse(){ document.body.classList.remove("using-kb"); }
    window.addEventListener("keydown",onKey,{passive:true});
    window.addEventListener("mousedown",onMouse,{passive:true});
}

/* Header sombra */
function elevateHeaderOnScroll(){
    const hdr=$(".appbar"); if(!hdr) return;
    const fn=()=>hdr.classList.toggle("scrolled",window.scrollY>4);
    window.addEventListener("scroll",fn,{passive:true}); fn();
}

/* Ripple */
function wireRipple(){
    $all(".button, .btn").forEach(btn=>{
        btn.addEventListener("click",(e)=>{
            if(btn.classList.contains("no-ripple")) return;
            const r=document.createElement("span"); r.className="ripple";
            const rect=btn.getBoundingClientRect(); const s=Math.max(rect.width,rect.height);
            Object.assign(r.style,{width:s+"px",height:s+"px",left:e.clientX-rect.left-s/2+"px",top:e.clientY-rect.top-s/2+"px"});
            btn.style.position="relative"; btn.appendChild(r); setTimeout(()=>r.remove(),450);
        },{passive:true});
    });
}

/* Code composer (si quisieras centralizarlo aquí también) */
function wireCodeComposer(){
    const prefix=$("#codePrefix"), suffix=$("#codeSuffix"), code=$("#code"), name=$("#name");
    if(!prefix||!suffix||!code) return;
    const EXT=".rma";
    const build=()=>{
        let suf=(suffix.value||"").replace(/[^0-9]/g,"");
        suffix.value=suf;
        const full=prefix.textContent+suf+EXT;
        code.value=full; if(name) name.value=full;
    };
    suffix.addEventListener("input",build,{passive:true}); build();
}

document.addEventListener("DOMContentLoaded",()=>{
    const t=$("#type");
    if(t){ t.addEventListener("change",toggleScope,{passive:true}); toggleScope(); }
    enableFocusVisible();
    elevateHeaderOnScroll();
    wireRipple();
    // wireCodeComposer(); // lo dejamos manejado por el inline para evitar caché
});
