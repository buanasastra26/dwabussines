<?php
function shop_header(string $title,array $user): void { ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#620707"><meta name="csrf-token" content="<?=e($_SESSION['csrf'])?>"><title><?=e($title)?> — DWA Bussines</title><link rel="stylesheet" href="shop.css?v=1"><link rel="manifest" href="manifest.webmanifest"><link rel="icon" href="icons/app-192.png"><script src="shop.js?v=1" defer></script></head><body><a class="skip" href="#main">Lewati ke konten</a><header class="shop-header"><a class="brand" href="dashboard.php">DWA<span>BUSSINES / CLIENT SPACE</span></a><nav aria-label="Menu client"><a href="dashboard.php">Belanja layanan</a><a href="cart.php" class="cart-link"><?=shop_icon('bag')?> Keranjang <b id="cart-count"><?=array_sum(array_column(shop_cart(),'qty'))?></b></a><button type="button" id="logout">Keluar ↗</button></nav></header><main id="main" class="shop-main"><div id="shop-status" class="status" role="status"></div>
<?php }
function shop_footer(): void { ?>
</main><footer class="shop-footer"><span>© <?=date('Y')?> DWA Bussines · PT Drajat Wiguna Adidaya</span><a href="privasi.html">Privasi akun</a><a href="dashboard.php#pasang-app">Pasang web app</a></footer></body></html>
<?php }
