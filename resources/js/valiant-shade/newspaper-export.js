const PAPER = '#f6ecd8';
const INK = '#2a1f14';
const MUTED_INK = '#6f5a41';
const RULE = 'rgba(42, 31, 20, 0.32)';
const WIDTH = 1200;
const PADDING = 76;
const CONTENT_WIDTH = WIDTH - PADDING * 2;

function normalizeText(value) {
  return String(value || '').replace(/\r\n?/g, '\n').trim();
}

function paragraphs(value) {
  return normalizeText(value)
    .split(/\n+/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean);
}

function wrapText(context, value, maxWidth) {
  const words = normalizeText(value).split(/\s+/).filter(Boolean);
  const lines = [];
  let line = '';

  words.forEach((word) => {
    const candidate = line ? `${line} ${word}` : word;
    if (context.measureText(candidate).width <= maxWidth || !line) {
      line = candidate;
      return;
    }

    lines.push(line);
    line = word;
  });

  if (line) {
    lines.push(line);
  }
  return lines;
}

function wrapParagraphs(context, value, maxWidth) {
  return paragraphs(value).map((paragraph) => wrapText(context, paragraph, maxWidth));
}

function drawLines(context, lines, x, y, lineHeight) {
  lines.forEach((line) => {
    context.fillText(line, x, y);
    y += lineHeight;
  });
  return y;
}

function drawParagraphs(context, blocks, x, y, lineHeight, paragraphGap) {
  blocks.forEach((lines) => {
    y = drawLines(context, lines, x, y, lineHeight);
    y += paragraphGap;
  });
  return y;
}

function createPaperCanvas(height) {
  const canvas = document.createElement('canvas');
  canvas.width = WIDTH;
  canvas.height = height;
  const context = canvas.getContext('2d');
  if (!context) {
    throw new Error('PNG çizim alanı oluşturulamadı.');
  }

  const gradient = context.createLinearGradient(0, 0, WIDTH, height);
  gradient.addColorStop(0, '#faf3e4');
  gradient.addColorStop(0.52, PAPER);
  gradient.addColorStop(1, '#ead7b4');
  context.fillStyle = gradient;
  context.fillRect(0, 0, WIDTH, height);

  context.strokeStyle = 'rgba(42, 31, 20, 0.68)';
  context.lineWidth = 5;
  context.strokeRect(22, 22, WIDTH - 44, height - 44);
  context.lineWidth = 1;
  context.strokeStyle = 'rgba(42, 31, 20, 0.2)';
  context.strokeRect(34, 34, WIDTH - 68, height - 68);
  return { canvas, context };
}

async function loadImage(url) {
  if (!url) {
    return null;
  }

  const image = new Image();
  image.crossOrigin = 'anonymous';
  image.decoding = 'async';
  image.src = url;
  try {
    await image.decode();
  } catch (error) {
    throw new Error(`Haber görseli yüklenemedi: ${url}`, { cause: error });
  }
  if (!image.naturalWidth || !image.naturalHeight) {
    throw new Error(`Haber görseli boş: ${url}`);
  }
  return image;
}

function drawCoverImage(context, image, x, y, width, height) {
  if (!image) {
    return;
  }

  const sourceAspect = image.naturalWidth / image.naturalHeight;
  const targetAspect = width / height;
  let sourceX = 0;
  let sourceY = 0;
  let sourceWidth = image.naturalWidth;
  let sourceHeight = image.naturalHeight;

  if (sourceAspect > targetAspect) {
    sourceWidth = image.naturalHeight * targetAspect;
    sourceX = (image.naturalWidth - sourceWidth) / 2;
  } else {
    sourceHeight = image.naturalWidth / targetAspect;
    sourceY = (image.naturalHeight - sourceHeight) / 2;
  }

  context.save();
  context.beginPath();
  context.rect(x, y, width, height);
  context.clip();
  context.drawImage(image, sourceX, sourceY, sourceWidth, sourceHeight, x, y, width, height);
  context.restore();
  context.strokeStyle = RULE;
  context.lineWidth = 2;
  context.strokeRect(x, y, width, height);
}

function toBlob(canvas) {
  return new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
      if (blob) {
        resolve(blob);
      } else {
        reject(new Error('PNG dosyası oluşturulamadı.'));
      }
    }, 'image/png');
  });
}

function drawCenteredText(context, text, y, font, color = INK) {
  context.font = font;
  context.fillStyle = color;
  context.textAlign = 'center';
  context.fillText(text, WIDTH / 2, y);
  context.textAlign = 'left';
}

export async function renderArticlePng(item) {
  if (!item || !item.id) {
    throw new Error('PNG için haber bilgisi eksik.');
  }
  await document.fonts.ready;

  const imageUrl = item.image_asset || item.image_url || '';
  const image = await loadImage(imageUrl);
  const title = normalizeText(item.title) || 'Başlıksız haber';
  const story = normalizeText(item.full_story || item.description);
  const titleFont = '700 52px Cinzel, Georgia, serif';
  const bodyFont = '30px Newsreader, Georgia, serif';
  const contentFont = '600 23px Newsreader, Georgia, serif';
  const measure = document.createElement('canvas').getContext('2d');
  if (!measure) {
    throw new Error('PNG metin ölçümü başlatılamadı.');
  }

  measure.font = titleFont;
  const titleLines = wrapText(measure, title, CONTENT_WIDTH);
  measure.font = bodyFont;
  const storyBlocks = wrapParagraphs(measure, story, CONTENT_WIDTH);
  measure.font = contentFont;
  const quoteLines = item.testimony ? wrapText(measure, item.testimony, CONTENT_WIDTH - 40) : [];

  const imageHeight = image ? Math.min(500, Math.max(300, CONTENT_WIDTH / (image.naturalWidth / image.naturalHeight))) : 0;
  const titleLineHeight = 66;
  const bodyLineHeight = 46;
  const quoteLineHeight = 36;
  const titleHeight = titleLines.length * titleLineHeight;
  const storyHeight = storyBlocks.reduce((sum, block) => sum + block.length * bodyLineHeight + 22, 0);
  const quoteHeight = quoteLines.length ? quoteLines.length * quoteLineHeight + 88 : 0;
  const height = Math.ceil(410 + titleHeight + imageHeight + storyHeight + quoteHeight);
  const { canvas, context } = createPaperCanvas(height);

  let y = 90;
  drawCenteredText(context, 'LAĞIMDAN SESLER', y, '700 27px Cinzel, Georgia, serif');
  y += 42;
  context.strokeStyle = RULE;
  context.lineWidth = 2;
  context.beginPath();
  context.moveTo(PADDING, y);
  context.lineTo(WIDTH - PADDING, y);
  context.stroke();
  y += 38;

  const category = normalizeText(item.news_type_label || item.category || 'Haber');
  const date = normalizeText(item.world_date_text || item.published_date || item.date_text);
  context.font = '600 22px Newsreader, Georgia, serif';
  context.fillStyle = MUTED_INK;
  context.fillText(category.toLocaleUpperCase('tr-TR'), PADDING, y);
  context.textAlign = 'right';
  context.fillText(date, WIDTH - PADDING, y);
  context.textAlign = 'left';
  y += 72;

  context.font = titleFont;
  context.fillStyle = INK;
  context.textAlign = 'center';
  y = drawLines(context, titleLines, WIDTH / 2, y, titleLineHeight);
  context.textAlign = 'left';
  y += 20;

  const authorParts = [
    normalizeText(item.author || 'Gazete Muhabiri'),
    normalizeText(item.author_role),
    normalizeText(item.location_badge),
  ].filter(Boolean);
  context.font = '600 24px Newsreader, Georgia, serif';
  context.fillStyle = MUTED_INK;
  context.textAlign = 'center';
  context.fillText(authorParts.join('  ·  '), WIDTH / 2, y);
  context.textAlign = 'left';
  y += 46;

  if (image) {
    drawCoverImage(context, image, PADDING, y, CONTENT_WIDTH, imageHeight);
    y += imageHeight + 44;
  }

  context.font = bodyFont;
  context.fillStyle = INK;
  y = drawParagraphs(context, storyBlocks, PADDING, y, bodyLineHeight, 22);

  if (quoteLines.length) {
    y += 20;
    context.fillStyle = 'rgba(97, 67, 36, 0.08)';
    context.fillRect(PADDING, y, CONTENT_WIDTH, quoteHeight - 22);
    context.font = 'italic 600 23px Newsreader, Georgia, serif';
    context.fillStyle = INK;
    y = drawLines(context, quoteLines, PADDING + 20, y + 42, quoteLineHeight);
  }

  context.strokeStyle = RULE;
  context.beginPath();
  context.moveTo(PADDING, height - 92);
  context.lineTo(WIDTH - PADDING, height - 92);
  context.stroke();
  drawCenteredText(context, 'Lağımdan Sesler Basın Bürosu', height - 55, 'italic 21px Newsreader, Georgia, serif', MUTED_INK);
  return toBlob(canvas);
}

function uniqueArticles(items) {
  const seen = new Set();
  return items.filter((item) => {
    if (!item || !item.id || seen.has(Number(item.id))) {
      return false;
    }
    seen.add(Number(item.id));
    return true;
  });
}

export async function renderGazettePagePng({ items = [], issue = {}, meta = {}, brand = {}, cornerSlot = null }) {
  await document.fonts.ready;
  const isCover = meta.sheet_kind === 'cover';
  const isExtra = meta.sheet_kind === 'extra';
  let articles = isCover
    ? uniqueArticles([issue.cover_lead, ...(issue.cover_briefs || []), ...(issue.cover_mastheads || [])])
    : uniqueArticles(items);
  if (isExtra && !articles.length && issue.cover_lead) {
    articles = [issue.cover_lead];
  }

  const images = await Promise.all(articles.map((item) => loadImage(item.image_asset || item.image_url || '')));
  const measure = document.createElement('canvas').getContext('2d');
  if (!measure) {
    throw new Error('PNG metin ölçümü başlatılamadı.');
  }

  const pageTitleFont = '700 48px Cinzel, Georgia, serif';
  const articleTitleFont = '700 34px Cinzel, Georgia, serif';
  const bodyFont = '26px Newsreader, Georgia, serif';
  let contentHeight = 360;
  const blocks = articles.map((item, index) => {
    measure.font = articleTitleFont;
    const titleLines = wrapText(measure, item.title || '', CONTENT_WIDTH);
    measure.font = bodyFont;
    const storyBlocks = wrapParagraphs(measure, item.full_story || item.description, CONTENT_WIDTH);
    const img = images[index];
    const imageHeight = img ? Math.min(360, Math.max(180, CONTENT_WIDTH / (img.naturalWidth / img.naturalHeight))) : 0;
    const blockHeight = 100 + titleLines.length * 45 + imageHeight
      + storyBlocks.reduce((sum, block) => sum + block.length * 38 + 18, 0);
    contentHeight += blockHeight + 48;
    return { item, titleLines, storyBlocks, image: img, imageHeight };
  });

  const cornerText = normalizeText(cornerSlot?.body);
  let cornerBlocks = [];
  if (cornerText) {
    measure.font = bodyFont;
    cornerBlocks = wrapParagraphs(measure, cornerText, CONTENT_WIDTH);
    contentHeight += cornerBlocks.reduce((sum, block) => sum + block.length * 38 + 18, 0) + 110;
  }

  const height = Math.ceil(contentHeight + 130);
  const { canvas, context } = createPaperCanvas(height);
  let y = 92;

  const brandName = normalizeText(brand.name || issue.title || 'Lağımdan Sesler');
  drawCenteredText(context, brandName.toLocaleUpperCase('tr-TR'), y, pageTitleFont);
  y += 56;
  const issueDate = normalizeText(meta.dateStr || issue.date_line || '');
  const issueNumber = normalizeText(issue.display_issue_number || issue.issue_number || meta.issueNumber || '');
  const pageNumber = normalizeText(meta.page || meta.current_page || '1');
  drawCenteredText(
    context,
    [issueDate, issueNumber ? `Sayı ${issueNumber}` : '', `Yaprak ${pageNumber}`].filter(Boolean).join('  ·  '),
    y,
    'italic 21px Newsreader, Georgia, serif',
    MUTED_INK,
  );
  y += 32;
  context.strokeStyle = RULE;
  context.lineWidth = 3;
  context.beginPath();
  context.moveTo(PADDING, y);
  context.lineTo(WIDTH - PADDING, y);
  context.stroke();
  y += 52;

  const heading = isExtra ? 'SICAK HABER' : isCover ? 'SAYININ GÜNDEMİ' : 'GAZETE YAPRAĞI';
  drawCenteredText(context, heading, y, '700 25px Cinzel, Georgia, serif', MUTED_INK);
  y += 62;

  blocks.forEach(({ item, titleLines, storyBlocks, image, imageHeight }, index) => {
    context.font = '600 20px Newsreader, Georgia, serif';
    context.fillStyle = MUTED_INK;
    context.fillText(normalizeText(item.news_type_label || item.category || 'Haber').toLocaleUpperCase('tr-TR'), PADDING, y);
    y += 48;

    context.font = articleTitleFont;
    context.fillStyle = INK;
    y = drawLines(context, titleLines, PADDING, y, 45);
    y += 12;

    if (image) {
      drawCoverImage(context, image, PADDING, y, CONTENT_WIDTH, imageHeight);
      y += imageHeight + 26;
    }

    context.font = bodyFont;
    context.fillStyle = INK;
    y = drawParagraphs(context, storyBlocks, PADDING, y, 38, 18);

    if (index < blocks.length - 1) {
      y += 16;
      context.strokeStyle = RULE;
      context.lineWidth = 1;
      context.beginPath();
      context.moveTo(PADDING, y);
      context.lineTo(WIDTH - PADDING, y);
      context.stroke();
      y += 40;
    }
  });

  if (cornerBlocks.length) {
    y += 24;
    context.strokeStyle = RULE;
    context.beginPath();
    context.moveTo(PADDING, y);
    context.lineTo(WIDTH - PADDING, y);
    context.stroke();
    y += 48;
    context.font = '700 29px Cinzel, Georgia, serif';
    context.fillStyle = INK;
    context.fillText(normalizeText(cornerSlot.title || 'Köşe Yazısı'), PADDING, y);
    y += 48;
    context.font = bodyFont;
    y = drawParagraphs(context, cornerBlocks, PADDING, y, 38, 18);
  }

  context.strokeStyle = RULE;
  context.beginPath();
  context.moveTo(PADDING, height - 84);
  context.lineTo(WIDTH - PADDING, height - 84);
  context.stroke();
  drawCenteredText(context, normalizeText(brand.press || 'Lağımdan Sesler Basın Bürosu'), height - 48, 'italic 20px Newsreader, Georgia, serif', MUTED_INK);
  return toBlob(canvas);
}
