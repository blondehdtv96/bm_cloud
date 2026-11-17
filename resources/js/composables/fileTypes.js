// File-type metadata used for coloured icons and labels across the app.
const TYPES = {
  pdf: { label: 'PDF', color: '#dc2626', bg: '#fdecec', kind: 'Dokumen PDF' },
  doc: { label: 'DOC', color: '#2563eb', bg: '#eaf1ff', kind: 'Dokumen Word' },
  sheet: { label: 'XLS', color: '#16a34a', bg: '#e8f7ee', kind: 'Spreadsheet' },
  slide: { label: 'PPT', color: '#ea580c', bg: '#fff1e8', kind: 'Presentasi' },
  image: { label: 'IMG', color: '#9333ea', bg: '#f4ecfe', kind: 'Gambar' },
  video: { label: 'VID', color: '#db2777', bg: '#fdebf4', kind: 'Video' },
  audio: { label: 'AUD', color: '#0891b2', bg: '#e6f6fa', kind: 'Audio' },
  archive: { label: 'ZIP', color: '#b45309', bg: '#fff7e6', kind: 'Arsip' },
  code: { label: 'CODE', color: '#475569', bg: '#eef1f6', kind: 'Kode' },
  text: { label: 'TXT', color: '#475569', bg: '#eef1f6', kind: 'Teks' },
  other: { label: 'FILE', color: '#64748b', bg: '#eef1f6', kind: 'File' },
};

const EXT = {
  pdf: 'pdf',
  doc: 'doc', docx: 'doc', odt: 'doc', rtf: 'doc',
  xls: 'sheet', xlsx: 'sheet', csv: 'sheet', ods: 'sheet',
  ppt: 'slide', pptx: 'slide', odp: 'slide',
  jpg: 'image', jpeg: 'image', png: 'image', gif: 'image', webp: 'image', svg: 'image', bmp: 'image', heic: 'image',
  mp4: 'video', mkv: 'video', mov: 'video', avi: 'video', webm: 'video',
  mp3: 'audio', wav: 'audio', ogg: 'audio', m4a: 'audio', flac: 'audio',
  zip: 'archive', rar: 'archive', '7z': 'archive', gz: 'archive', tar: 'archive',
  js: 'code', ts: 'code', php: 'code', py: 'code', html: 'code', css: 'code', json: 'code', sql: 'code', xml: 'code',
  txt: 'text', md: 'text', log: 'text',
};

export const extensionOf = (name = '') => {
  const dot = name.lastIndexOf('.');
  return dot > 0 ? name.slice(dot + 1).toLowerCase() : '';
};

export const fileType = (name) => {
  const ext = extensionOf(name);
  const type = TYPES[EXT[ext] || 'other'];
  return { ...type, ext, label: EXT[ext] ? type.label : (ext ? ext.slice(0, 4).toUpperCase() : type.label) };
};

/** Trigger a browser download for an axios blob response. */
export const saveBlobResponse = (response, fallbackName) => {
  const disposition = response.headers?.['content-disposition'] || '';
  const match = disposition.match(/filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i);
  const filename = match ? decodeURIComponent(match[1] || match[2]) : fallbackName;
  const url = window.URL.createObjectURL(response.data instanceof Blob ? response.data : new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => window.URL.revokeObjectURL(url), 1000);
  return filename;
};

/** Axios blob error bodies are Blobs; pull the JSON "message" out of them. */
export const blobErrorMessage = async (error, fallback) => {
  const data = error?.response?.data;
  if (data instanceof Blob) {
    try { return JSON.parse(await data.text()).message || fallback; } catch { return fallback; }
  }
  return data?.message || fallback;
};
