<?php
if (!defined('ABSPATH')) exit;

add_shortcode('nk_vouchers', 'nk_vouchers_shortcode');

function nk_vouchers_shortcode() {
    $b = plugin_dir_url(__FILE__) . 'neogen-theme-assets/img/brands/';
    ob_start();
    ?>
<style>
#nk-vouchers-wrap *{box-sizing:border-box;}
#nk-vouchers-wrap{font-family:'DM Sans','Helvetica Neue',sans-serif;margin:0 -16px;}
.nk-v-hero{padding:48px 16px 36px;background:#FAFAF8;border-bottom:1px solid #E3DDD4;}
.nk-v-label{display:inline-flex;align-items:center;gap:8px;background:#EFF9FF;border:1px solid rgba(56,189,248,.3);color:#0EA5E9;font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:100px;margin-bottom:18px;}
.nk-v-label::before{content:'';width:6px;height:6px;background:#38BDF8;border-radius:50%;animation:nkpulse 2s ease-in-out infinite;}
@keyframes nkpulse{0%,100%{opacity:1}50%{opacity:.4}}
.nk-v-hero h2{font-size:clamp(32px,5vw,52px);font-weight:800;letter-spacing:-.03em;line-height:1;color:#181714;margin-bottom:12px;}
.nk-v-hero h2 em{font-style:normal;color:#38BDF8;}
.nk-v-sub{font-size:15px;font-weight:300;color:#78746E;max-width:480px;line-height:1.65;margin-bottom:28px;}
.nk-v-stats{display:flex;gap:28px;flex-wrap:wrap;}
.nk-v-stat-num{font-size:26px;font-weight:800;letter-spacing:-.04em;color:#181714;display:block;line-height:1;}
.nk-v-stat-lbl{font-size:11px;color:#ABA79F;}

.nk-v-filters{padding:24px 16px 8px;display:flex;gap:8px;flex-wrap:wrap;}
.nk-vf-btn{background:#fff;border:1px solid #E3DDD4;color:#78746E;font-size:13px;font-weight:500;padding:7px 16px;border-radius:100px;cursor:pointer;transition:all .18s;font-family:inherit;}
.nk-vf-btn:hover{border-color:#38BDF8;color:#0EA5E9;}
.nk-vf-btn.nk-active{background:#181714;border-color:#181714;color:#fff;}
.nk-vf-count{font-size:11px;opacity:.55;margin-left:2px;}

.nk-v-section{padding:20px 16px 4px;}
.nk-v-section.nk-hidden{display:none;}
.nk-v-cat-lbl{font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#ABA79F;display:flex;align-items:center;gap:12px;margin-bottom:16px;}
.nk-v-cat-lbl::after{content:'';flex:1;height:1px;background:#E3DDD4;}

.nk-v-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
@media(max-width:900px){.nk-v-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:600px){.nk-v-grid{grid-template-columns:repeat(2,1fr);gap:10px;}}

.nk-card{background:#fff;border:1px solid #E3DDD4;border-radius:14px;overflow:hidden;cursor:pointer;display:flex;flex-direction:column;transition:transform .22s cubic-bezier(.25,.46,.45,.94),border-color .22s,box-shadow .22s;box-shadow:0 1px 3px rgba(0,0,0,.05),0 4px 12px rgba(0,0,0,.04);}
.nk-card:hover{transform:translateY(-5px);border-color:#38BDF8;box-shadow:0 8px 24px rgba(14,165,233,.14),0 2px 8px rgba(0,0,0,.06);}
.nk-card:hover .nk-card-arrow{background:#38BDF8;color:#fff;border-color:#38BDF8;}
.nk-card:hover .nk-card-logo img{transform:scale(1.05);}

.nk-card-top{padding:14px 14px 0;display:flex;align-items:center;justify-content:space-between;}
.nk-card-cat{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#ABA79F;}
.nk-badge{font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:3px 7px;border-radius:100px;}
.nk-badge-hot{background:#FEF2F2;color:#DC2626;}
.nk-badge-new{background:#EFF9FF;color:#0EA5E9;}
.nk-badge-pop{background:#F0FDF4;color:#16A34A;}

.nk-card-logo{height:96px;display:flex;align-items:center;justify-content:center;padding:16px 20px;}
.nk-card-logo img{max-width:100%;max-height:64px;object-fit:contain;transition:transform .22s ease;}

.nk-card-bottom{padding:11px 14px 14px;display:flex;align-items:flex-end;justify-content:space-between;border-top:1px solid #F0EDE8;margin-top:auto;}
.nk-card-name{font-weight:700;font-size:13px;color:#181714;letter-spacing:-.01em;line-height:1.2;}
.nk-card-range{font-size:11px;color:#ABA79F;margin-top:3px;font-weight:400;}
.nk-card-arrow{width:32px;height:32px;border-radius:8px;background:#F7F6F3;border:1px solid #E3DDD4;display:flex;align-items:center;justify-content:center;color:#78746E;font-size:14px;transition:all .18s;flex-shrink:0;}
</style>

<div id="nk-vouchers-wrap">

  <div class="nk-v-hero">
    <div class="nk-v-label">Digital Products</div>
    <h2>Vouchers &amp; <em>Gift Cards.</em></h2>
    <p class="nk-v-sub">Instant digital delivery. Top-up and gift cards for gaming, entertainment, shopping, and more.</p>
    <div class="nk-v-stats">
      <div><span class="nk-v-stat-num">15+</span><span class="nk-v-stat-lbl">Brands available</span></div>
      <div><span class="nk-v-stat-num">SAR 15–2000</span><span class="nk-v-stat-lbl">Value range</span></div>
      <div><span class="nk-v-stat-num">&lt; 60s</span><span class="nk-v-stat-lbl">Instant delivery</span></div>
    </div>
  </div>

  <div class="nk-v-filters">
    <button class="nk-vf-btn nk-active" data-cat="all" onclick="nkFilter(this)">All <span class="nk-vf-count">15</span></button>
    <button class="nk-vf-btn" data-cat="gaming"         onclick="nkFilter(this)">Gaming <span class="nk-vf-count">6</span></button>
    <button class="nk-vf-btn" data-cat="shopping"       onclick="nkFilter(this)">Shopping <span class="nk-vf-count">3</span></button>
    <button class="nk-vf-btn" data-cat="entertainment"  onclick="nkFilter(this)">Entertainment <span class="nk-vf-count">3</span></button>
    <button class="nk-vf-btn" data-cat="telecom"        onclick="nkFilter(this)">Telecom <span class="nk-vf-count">1</span></button>
    <button class="nk-vf-btn" data-cat="productivity"   onclick="nkFilter(this)">Productivity <span class="nk-vf-count">2</span></button>
  </div>

  <!-- GAMING -->
  <div class="nk-v-section" data-section="gaming">
    <div class="nk-v-cat-lbl">Gaming</div>
    <div class="nk-v-grid">
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span><span class="nk-badge nk-badge-hot">Popular</span></div><div class="nk-card-logo"><img src="<?= $b ?>playstation.svg" alt="PlayStation Store"></div><div class="nk-card-bottom"><div><div class="nk-card-name">PlayStation Store</div><div class="nk-card-range">SAR 50–500</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span><span class="nk-badge nk-badge-pop">Trending</span></div><div class="nk-card-logo"><img src="<?= $b ?>xbox.svg" alt="Xbox"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Xbox</div><div class="nk-card-range">SAR 50–500</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span></div><div class="nk-card-logo"><img src="<?= $b ?>steam.svg" alt="Steam"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Steam</div><div class="nk-card-range">SAR 25–300</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span><span class="nk-badge nk-badge-hot">Hot</span></div><div class="nk-card-logo"><img src="<?= $b ?>pubg.svg" alt="PUBG Mobile"></div><div class="nk-card-bottom"><div><div class="nk-card-name">PUBG Mobile</div><div class="nk-card-range">SAR 15–200</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span></div><div class="nk-card-logo"><img src="<?= $b ?>ROBLOX.svg" alt="Roblox"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Roblox</div><div class="nk-card-range">SAR 15–150</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span><span class="nk-badge nk-badge-new">New</span></div><div class="nk-card-logo"><img src="<?= $b ?>Google_Play.svg" alt="Google Play"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Google Play</div><div class="nk-card-range">SAR 25–400</div></div><div class="nk-card-arrow">→</div></div></div>
    </div>
  </div>

  <!-- SHOPPING -->
  <div class="nk-v-section" data-section="shopping">
    <div class="nk-v-cat-lbl">Shopping</div>
    <div class="nk-v-grid">
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Shopping</span><span class="nk-badge nk-badge-hot">Popular</span></div><div class="nk-card-logo"><img src="<?= $b ?>amazon.svg" alt="Amazon"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Amazon</div><div class="nk-card-range">SAR 50–1000</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Shopping</span></div><div class="nk-card-logo"><img src="<?= $b ?>NOON.svg" alt="NOON"></div><div class="nk-card-bottom"><div><div class="nk-card-name">NOON</div><div class="nk-card-range">SAR 50–2000</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Shopping</span></div><div class="nk-card-logo"><img src="<?= $b ?>KSP.svg" alt="KSP"></div><div class="nk-card-bottom"><div><div class="nk-card-name">KSP</div><div class="nk-card-range">SAR 100–2000</div></div><div class="nk-card-arrow">→</div></div></div>
    </div>
  </div>

  <!-- ENTERTAINMENT -->
  <div class="nk-v-section" data-section="entertainment">
    <div class="nk-v-cat-lbl">Entertainment</div>
    <div class="nk-v-grid">
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Entertainment</span><span class="nk-badge nk-badge-pop">Trending</span></div><div class="nk-card-logo"><img src="<?= $b ?>AMAZON_PRIME.svg" alt="Amazon Prime"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Amazon Prime</div><div class="nk-card-range">SAR 30–200</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Entertainment</span></div><div class="nk-card-logo"><img src="<?= $b ?>Twitch.svg" alt="Twitch"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Twitch</div><div class="nk-card-range">SAR 25–150</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Entertainment</span></div><div class="nk-card-logo"><img src="<?= $b ?>mcafee.svg" alt="McAfee"></div><div class="nk-card-bottom"><div><div class="nk-card-name">McAfee</div><div class="nk-card-range">SAR 60–300</div></div><div class="nk-card-arrow">→</div></div></div>
    </div>
  </div>

  <!-- TELECOM -->
  <div class="nk-v-section" data-section="telecom">
    <div class="nk-v-cat-lbl">Telecom</div>
    <div class="nk-v-grid">
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Telecom</span><span class="nk-badge nk-badge-hot">Popular</span></div><div class="nk-card-logo"><img src="<?= $b ?>stc.svg" alt="STC"></div><div class="nk-card-bottom"><div><div class="nk-card-name">STC Cards</div><div class="nk-card-range">SAR 30–500</div></div><div class="nk-card-arrow">→</div></div></div>
    </div>
  </div>

  <!-- PRODUCTIVITY -->
  <div class="nk-v-section" data-section="productivity">
    <div class="nk-v-cat-lbl">Productivity</div>
    <div class="nk-v-grid">
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Productivity</span></div><div class="nk-card-logo"><img src="<?= $b ?>Adobe.svg" alt="Adobe"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Adobe</div><div class="nk-card-range">SAR 50–500</div></div><div class="nk-card-arrow">→</div></div></div>
      <div class="nk-card"><div class="nk-card-top"><span class="nk-card-cat">Productivity</span></div><div class="nk-card-logo"><img src="<?= $b ?>microsoft.svg" alt="Microsoft"></div><div class="nk-card-bottom"><div><div class="nk-card-name">Microsoft</div><div class="nk-card-range">SAR 50–800</div></div><div class="nk-card-arrow">→</div></div></div>
    </div>
  </div>

</div>

<script>
function nkFilter(btn){
  var cat=btn.dataset.cat;
  document.querySelectorAll('.nk-vf-btn').forEach(function(b){b.classList.remove('nk-active');});
  btn.classList.add('nk-active');
  document.querySelectorAll('.nk-v-section').forEach(function(s){
    s.classList.toggle('nk-hidden', cat!=='all' && s.dataset.section!==cat);
  });
}
</script>
    <?php
    return ob_get_clean();
}
