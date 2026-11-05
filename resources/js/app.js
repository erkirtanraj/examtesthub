import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
createInertiaApp({title:title=>title?\x60\x24{title} · ExamTestHub\x60:'ExamTestHub',resolve:name=>{const pages=import.meta.glob('./Pages/**/*.vue',{eager:true});return pages[\x60./Pages/\x24{name}.vue\x60];},setup({el,App,props,plugin}){createApp({render:()=>h(App,props)}).use(plugin).mount(el);},progress:{color:'#4f46e5'}});
