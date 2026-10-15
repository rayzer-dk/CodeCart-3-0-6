<?php
class ControllerInformationPublicDocument extends Controller {
    public function download() {
        $id=isset($this->request->get['document_id'])?(int)$this->request->get['document_id']:0;
        if ($id<1 || !$this->config->get('codecart_public_documents_status')) { $this->notFound();return; }
        $this->load->model('catalog/public_document');
        $document=$this->model_catalog_public_document->getPublic($id);
        if (!$document) { $this->notFound();return; }
        $base=realpath(rtrim(DIR_STORAGE,'/\\').'/public_documents');
        $filename=(string)$document['filename'];
        if (!$base || !preg_match('/^[a-f0-9]{40}\.(?:pdf|docx?|xlsx?|jpe?g|png|webp|txt|zip)$/D',$filename)) { $this->notFound();return; }
        $file=realpath($base.'/'.$filename);
        if (!$file || strpos($file,$base.DIRECTORY_SEPARATOR)!==0 || !is_file($file) || !is_readable($file)) { $this->notFound();return; }
        $mask=basename(str_replace('\\','/',(string)$document['original_name']));
        $mask=preg_replace('/[\x00-\x1F\x7F"\\\\]+/','_',$mask);
        if (!$mask)$mask='document.bin';
        if (headers_sent()) { $this->notFound();return; }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.preg_replace('/[^\x20-\x7e]/','_',$mask).'"; filename*=UTF-8\'\''.rawurlencode($mask));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private,no-store,max-age=0');
        header('Content-Length: '.(int)filesize($file));
        while (ob_get_level()>0) ob_end_clean();
        $bytes=readfile($file);
        if ($bytes!==false && $bytes>0) {
            try { $this->model_catalog_public_document->increment($id); }
            catch (\Throwable $e) { $this->log->write('Public document stats failed: '.$e->getMessage()); }
        }
        exit();
    }
    private function notFound() { $this->response->setStatusCode(404);$this->response->setOutput('Document not found'); }
}
