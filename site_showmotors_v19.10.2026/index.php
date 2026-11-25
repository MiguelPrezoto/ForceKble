<?php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oficina Show Motors</title>
    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* ── Marcas ── */
        .secao-marcas {
            background: #fff;
            padding: 30px 40px;
            border-bottom: 1px solid #eee;
        }
        .marcas-faixa {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 32px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .marcas-faixa img {
            height: 36px;
            width: auto;
            opacity: 0.45;
            filter: grayscale(100%);
            transition: opacity 0.2s, filter 0.2s;
        }
        .marcas-faixa img:hover {
            opacity: 0.85;
            filter: grayscale(0%);
        }

        /* ── Hero ── */
        .hero {
            position: relative;
            height: 520px;
            background: url('assets/oficina.png') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 60px;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(0,0,0,0.72) 40%, rgba(0,0,0,0.1));
        }
        .hero-conteudo {
            position: relative;
            color: #fff;
            max-width: 560px;
        }
        .hero-conteudo h2 {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .hero-conteudo h2 span {
            color: #eb2525;
        }
        .hero-conteudo p {
            font-size: 1.05rem;
            opacity: 0.88;
            margin-bottom: 28px;
            line-height: 1.6;
        }
        .hero-btn {
            display: inline-block;
            background: #eb2525;
            color: #fff;
            padding: 13px 32px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: background 0.2s;
        }
        .hero-btn:hover { background: #c41e1e; }

        /* ── Serviços ── */
        .secao {
            padding: 60px 40px;
            background: #fff;
        }
        .secao-titulo {
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        .secao-sub {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
            font-size: 0.95rem;
        }
        .secao-divider {
            width: 50px;
            height: 3px;
            background: #eb2525;
            margin: 0 auto 40px;
            border-radius: 2px;
        }
        .grid-servicos {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .card-servico {
            background: #f8f9fb;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 48px 32px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-servico:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.09);
        }
        .card-servico i {
            font-size: 2.2rem;
            color: #eb2525;
            margin-bottom: 14px;
        }
        .card-servico h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        .card-servico p {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.5;
        }

        /* ── Sobre teaser ── */
        .secao-sobre {
            padding: 60px 40px;
            background: #f5f6fa;
            display: flex;
            align-items: center;
            gap: 50px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .sobre-img {
            flex: 1;
            border-radius: 8px;
            overflow: hidden;
            max-width: 440px;
        }
        .sobre-img img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
        }
        .sobre-texto {
            flex: 1;
        }
        .sobre-texto h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: #1a1a1a;
        }
        .sobre-texto p {
            color: #555;
            line-height: 1.7;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .sobre-texto a {
            color: #eb2525;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .sobre-texto a:hover { text-decoration: underline; }

        /* ── Footer ── */
        .rodape {
            background: #1a1a1a;
            color: #ccc;
            padding: 36px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
            flex-wrap: wrap;
        }
        .rodape h4 {
            color: #fff;
            font-size: 1rem;
            margin-bottom: 12px;
        }
        .rodape p, .rodape a {
            font-size: 0.88rem;
            color: #aaa;
            text-decoration: none;
            display: block;
            margin-bottom: 6px;
            line-height: 1.5;
        }
        .rodape a:hover { color: #eb2525; }
        .rodape-marca {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 1px;
        }

    </style>
</head>
<body>

    <header class="topo">
        <div class="lado-esquerdo">
            <h1 class="titulo">ShowMotors</h1>
            <nav class="nav-principal">
                <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Entrar</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-conteudo">
            <h2>Sua oficina de<br>confiança há <span>20 anos</span></h2>
            <p>Mecânica geral, injeção eletrônica, diagnóstico eletrônico<br>
               e muito mais. Atendemos veículos nacionais e importados.</p>
            <a href="login.php" class="hero-btn">Agendar serviço</a>
        </div>
    </section>

    <!-- Marcas -->
    <section class="secao-marcas">
        <div class="marcas-faixa">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/chevrolet.svg"     alt="Chevrolet"    title="Chevrolet">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/alfaromeo.svg"     alt="Alfa Romeo"   title="Alfa Romeo">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/fiat.svg"          alt="Fiat"         title="Fiat">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/ford.svg"          alt="Ford"         title="Ford">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/peugeot.svg"       alt="Peugeot"      title="Peugeot">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/suzuki.svg"        alt="Suzuki"       title="Suzuki">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/nissan.svg"        alt="Nissan"       title="Nissan">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/bmw.svg"           alt="BMW"          title="BMW">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/volkswagen.svg"    alt="Volkswagen"   title="Volkswagen">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/subaru.svg"        alt="Subaru"       title="Subaru">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/kia.svg"           alt="KIA"          title="KIA">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/citroen.svg"       alt="Citroën"      title="Citroën">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@10/icons/audi.svg"          alt="Audi"         title="Audi">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/mercedes.svg"  alt="Mercedes-Benz" title="Mercedes-Benz">
        </div>
    </section>

    <!-- Serviços -->
    <section class="secao">
        <h2 class="secao-titulo">Nossos Serviços</h2>
        <p class="secao-sub">Qualidade e especialização em cada atendimento</p>
        <div class="secao-divider"></div>
        <div class="grid-servicos">
            <div class="card-servico">
                <i class="fa-solid fa-car-burst"></i>
                <h3>Air Bag</h3>
                <p>Diagnóstico e reparo completo no sistema de air bags do seu veículo.</p>
            </div>
            <div class="card-servico">
                <i class="fa-solid fa-microchip"></i>
                <h3>Injeção Eletrônica</h3>
                <p>Especialistas em diagnóstico e reparo de módulos de injeção.</p>
            </div>
            <div class="card-servico">
                <i class="fa-solid fa-oil-can"></i>
                <h3>Troca de Óleo</h3>
                <p>Troca com produtos de alta qualidade para o melhor desempenho.</p>
            </div>
            <div class="card-servico">
                <i class="fa-solid fa-circle-stop"></i>
                <h3>Freios</h3>
                <p>Revisão e substituição de pastilhas, discos e sistema ABS.</p>
            </div>
            <div class="card-servico">
                <i class="fa-solid fa-car"></i>
                <h3>Suspensão</h3>
                <p>Alinhamento, balanceamento e revisão completa da suspensão.</p>
            </div>
            <div class="card-servico">
                <i class="fa-solid fa-key"></i>
                <h3>Chaves Codificadas</h3>
                <p>Programação de chaves e controles para todos os modelos.</p>
            </div>
        </div>
    </section>

    <!-- Sobre teaser -->
    <div style="background:#f5f6fa; padding: 60px 40px;">
        <div class="secao-sobre" style="padding:0">
            <div class="sobre-img">
                <img src="assets/mecanicaLogin.png" alt="Oficina Show Motors">
            </div>
            <div class="sobre-texto">
                <h2>Quem Somos?</h2>
                <p>Fundada em 2006 como RK Dias, a Oficina Show Motors nasceu com o desafio
                   de oferecer serviços de alta qualidade com peças originais em uma região
                   já repleta de oficinas conceituadas.</p>
                <p>Com o passar dos anos, nos especializamos em tecnologias avançadas: injeção
                   eletrônica, ABS, EPS, Air Bag e gerenciamento eletrônico de veículos
                   nacionais e importados.</p>
                <a href="sobre.php">Saiba mais →</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="rodape">
        <div>
            <span class="rodape-marca">Show Motors</span>
            <p style="margin-top:8px">Oficina de referência desde 2006.</p>
        </div>
        <div>
            <h4>Contato</h4>
            <p><i class="fa-brands fa-whatsapp"></i> (11) 4677-6357</p>
            <p><i class="fa-solid fa-location-dot"></i> Estr. de Poá, 1278 - Jardim Soares, SP</p>
            <iframe
                src="https://maps.google.com/maps?q=Estrada+de+Po%C3%A1,+1278,+S%C3%A3o+Paulo,+SP,+08460-245&output=embed"
                width="240" height="150" style="border:0; border-radius:6px; margin:10px auto 0; display:block;"
                allowfullscreen loading="lazy"></iframe>
        </div>
        <div>
            <h4>Acesso</h4>
            <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Entrar no sistema</a>
            <a href="sobre.php"><i class="fa-solid fa-circle-info"></i> Sobre nós</a>
        </div>
    </footer>

</body>
</html>
