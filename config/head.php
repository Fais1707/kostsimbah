<?php
$tailwind_config = <<<'JS'
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "on-secondary-fixed-variant":"#474839","secondary":"#5f5f50","surface-container":"#edeeea",
        "primary-fixed-dim":"#a5d0b9","surface-container-lowest":"#ffffff","on-tertiary":"#ffffff",
        "on-primary-container":"#86af99","error":"#ba1a1a","on-primary-fixed":"#002114",
        "on-secondary":"#ffffff","on-background":"#1a1c1a","on-tertiary-fixed":"#2c1603",
        "on-surface-variant":"#414844","on-primary":"#ffffff","error-container":"#ffdad6",
        "on-tertiary-container":"#c69f80","on-error":"#ffffff","inverse-on-surface":"#f0f1ed",
        "surface":"#f9faf6","primary":"#012d1d","tertiary-fixed":"#ffdcc2",
        "on-primary-fixed-variant":"#274e3d","tertiary-fixed-dim":"#e9be9e",
        "on-secondary-container":"#656555","inverse-surface":"#2e312f","primary-fixed":"#c1ecd4",
        "surface-dim":"#d9dad7","on-error-container":"#93000a","secondary-fixed":"#e4e4cf",
        "surface-bright":"#f9faf6","on-tertiary-fixed-variant":"#5e4028","inverse-primary":"#a5d0b9",
        "background":"#f9faf6","primary-container":"#1b4332","surface-container-low":"#f3f4f0",
        "on-surface":"#1a1c1a","surface-container-high":"#e7e9e5","tertiary-container":"#51361e",
        "surface-variant":"#e2e3df","secondary-container":"#e4e4cf","secondary-fixed-dim":"#c8c8b4",
        "surface-tint":"#3f6653","outline-variant":"#c1c8c2","tertiary":"#39210b",
        "surface-container-highest":"#e2e3df","outline":"#717973","on-secondary-fixed":"#1b1d10"
      },
      borderRadius:{"DEFAULT":"0.25rem","lg":"0.5rem","xl":"0.75rem","full":"9999px"},
      spacing:{"base":"4px","container-max":"1280px","sm":"16px","md":"24px","xs":"8px","xl":"64px","gutter":"20px","lg":"40px"},
      fontFamily:{"body-md":["Inter"],"headline-lg":["Montserrat"],"display-lg":["Montserrat"],"title-md":["Montserrat"],"body-lg":["Inter"],"label-md":["Inter"],"label-sm":["Inter"]},
      fontSize:{
        "body-md":["16px",{"lineHeight":"24px","fontWeight":"400"}],
        "headline-lg":["32px",{"lineHeight":"40px","fontWeight":"600"}],
        "display-lg":["48px",{"lineHeight":"56px","letterSpacing":"-0.02em","fontWeight":"700"}],
        "title-md":["20px",{"lineHeight":"28px","fontWeight":"600"}],
        "headline-lg-mobile":["24px",{"lineHeight":"32px","fontWeight":"600"}],
        "body-lg":["18px",{"lineHeight":"28px","fontWeight":"400"}],
        "label-md":["14px",{"lineHeight":"20px","letterSpacing":"0.01em","fontWeight":"500"}],
        "label-sm":["12px",{"lineHeight":"16px","fontWeight":"600"}]
      }
    }
  }
}
JS;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $pageTitle ?? 'Kost Simbah' ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="icon" type="image/png" href="/kost_simbah/assets/img/favicon.png"/>
<script><?= $tailwind_config ?></script>
<style>
  .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
  .glass-card{background:rgba(249,250,246,0.8);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.3)}
  body{min-height:max(884px,100dvh)}
  html{scroll-behavior:smooth}
  .custom-scrollbar::-webkit-scrollbar{width:4px}
  .custom-scrollbar::-webkit-scrollbar-thumb{background:#c1c8c2;border-radius:10px}
  .hero-zoom{transform-origin:center center;animation:heroZoom 18s ease-in-out infinite alternate;transition:transform 1.2s ease-in-out;}
  @keyframes heroZoom{0%{transform:scale(1);}100%{transform:scale(1.08);}}

  /* ===== REVEAL ANIMATIONS ===== */
  .reveal,.reveal-zoom,.reveal-left,.reveal-right{
    opacity:0;
    transition:opacity 0.7s ease, transform 0.7s ease;
  }
  .reveal        { transform: translateY(40px); }
  .reveal-zoom   { transform: scale(0.92) translateY(30px); }
  .reveal-left   { transform: translateX(-50px); }
  .reveal-right  { transform: translateX(50px); }

  .reveal.visible, .reveal-zoom.visible,
  .reveal-left.visible, .reveal-right.visible {
    opacity: 1;
    transform: none;
  }

  /* Stagger delay untuk grid items */
  .reveal-zoom:nth-child(1),.reveal:nth-child(1){ transition-delay: 0ms; }
  .reveal-zoom:nth-child(2),.reveal:nth-child(2){ transition-delay: 80ms; }
  .reveal-zoom:nth-child(3),.reveal:nth-child(3){ transition-delay: 160ms; }
  .reveal-zoom:nth-child(4),.reveal:nth-child(4){ transition-delay: 240ms; }
  .reveal-zoom:nth-child(5),.reveal:nth-child(5){ transition-delay: 320ms; }
  .reveal-zoom:nth-child(6),.reveal:nth-child(6){ transition-delay: 400ms; }
</style>
</head>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var selectors = '.reveal, .reveal-zoom, .reveal-left, .reveal-right';
    var elements = document.querySelectorAll(selectors);

    if (!elements.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    elements.forEach(function (el) {
      observer.observe(el);
    });
  });
</script>