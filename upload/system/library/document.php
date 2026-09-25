<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Document class
*/
class Document {
	private $title;
	private $robots;
	private $description;
	private $keywords;

	private $links = array();
	private $styles = array();
	private $scripts = array();
	private $og_image;
	private $og_type = 'website';
	private $structured_data = array();
	private $assets = array();

	/**
     * 
     *
     * @param	string	$title
     */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
     * 
	 * 
	 * @return	string
     */
	public function getTitle() {
		return $this->title;
	}
	
	public function setRobots($robots) {
		$this->robots = $robots;
	}
	
	public function getRobots() {
		return $this->robots;
	}

	/**
     * 
     *
     * @param	string	$description
     */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
     * 
     *
     * @param	string	$description
	 * 
	 * @return	string
     */
	public function getDescription() {
		return $this->description;
	}

	/**
     * 
     *
     * @param	string	$keywords
     */
	public function setKeywords($keywords) {
		$this->keywords = $keywords;
	}

	/**
     *
	 * 
	 * @return	string
     */
	public function getKeywords() {
		return $this->keywords;
	}
	
	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$rel
     */
	public function addLink($href, $rel) {
		$this->links[$href] = array(
			'href' => $href,
			'rel'  => $rel
		);
	}

	/**
     * 
	 * 
	 * @return	array
     */
	public function getLinks() {
		return $this->links;
	}

	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$rel
	 * @param	string	$media
     */
	public function addStyle($href, $rel = 'stylesheet', $media = 'screen', $position = 'header') {
		$this->styles[$position][$href] = array(
			'href'  => $href,
			'rel'   => $rel,
			'media' => $media
		);
	}

	/**
     * 
	 * 
	 * @return	array
     */
	public function getStyles($position = 'header') {
		if (isset($this->styles[$position])) {
			return $this->styles[$position];
		} else {
			return array();
		}
	}

	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$position
     */
	public function addScript($href, $position = 'header') {
		$this->scripts[$position][$href] = $href;
	}

	/**
     * 
     *
     * @param	string	$position
	 * 
	 * @return	array
     */
	public function getScripts($position = 'header') {
		if (isset($this->scripts[$position])) {
			return $this->scripts[$position];
		} else {
			return array();
		}
	}
	
	/**
	 * Register JSON-LD data for the current document.
	 *
	 * A key may be supplied to let core pages replace their own block without
	 * interfering with structured data added by third-party extensions.
	 */
	public function addStructuredData(array $data, $key = '') {
		if ($key !== '') {
			$this->structured_data[(string)$key] = $data;
		} else {
			$this->structured_data[] = $data;
		}
	}

	public function getStructuredData() {
		return $this->structured_data;
	}


    /**
     * Modern additive asset API. Legacy addScript()/addStyle() remain unchanged.
     * Supported keys: type(script|style), src/href, position(header|footer),
     * dependencies[], priority, defer, async, module, preload, media, version.
     */
    public function addAsset($id, array $asset) {
        $id = strtolower(trim((string)$id));
        if (!preg_match('/^[a-z][a-z0-9_.:-]{1,95}$/', $id)) { throw new \InvalidArgumentException('Invalid asset id.'); }
        $type = isset($asset['type']) ? strtolower((string)$asset['type']) : '';
        if (!in_array($type, array('script','style'), true)) { throw new \InvalidArgumentException('Invalid asset type.'); }
        $sourceKey = $type === 'script' ? 'src' : 'href';
        $source = isset($asset[$sourceKey]) ? trim((string)$asset[$sourceKey]) : '';
        if ($source === '' || preg_match('/[\r\n\0]/', $source)) { throw new \InvalidArgumentException('Invalid asset source.'); }
        $asset[$sourceKey] = $source;
        $asset['type'] = $type;
        $asset['position'] = (isset($asset['position']) && $asset['position'] === 'footer') ? 'footer' : 'header';
        $asset['priority'] = isset($asset['priority']) ? max(-1000, min(1000, (int)$asset['priority'])) : 100;
        $deps = isset($asset['dependencies']) && is_array($asset['dependencies']) ? $asset['dependencies'] : array();
        $asset['dependencies'] = array_values(array_unique(array_filter(array_map(function($v){$v=strtolower(trim((string)$v));return preg_match('/^[a-z][a-z0-9_.:-]{1,95}$/',$v)?$v:'';},$deps))));
        $asset['defer'] = !empty($asset['defer']); $asset['async'] = !empty($asset['async']); $asset['module'] = !empty($asset['module']); $asset['preload'] = !empty($asset['preload']);
        $asset['version'] = isset($asset['version']) ? preg_replace('/[^A-Za-z0-9._-]/','',(string)$asset['version']) : '';
        $asset['media'] = isset($asset['media']) ? substr(trim((string)$asset['media']),0,64) : 'screen';
        $asset['url'] = $source;
        if ($asset['version'] !== '') {
            $asset['url'] .= (strpos($source, '?') === false ? '?' : '&') . 'v=' . rawurlencode($asset['version']);
        }
        $this->assets[$id] = $asset;
    }

    public function getAssets($position = 'header') {
        $position = $position === 'footer' ? 'footer' : 'header';
        $selected = array_filter($this->assets, function($asset) use ($position){ return $asset['position'] === $position && empty($asset['legacy_bridge']); });
        $ordered = array(); $visiting = array(); $done = array();
        $visit = function($id) use (&$visit,&$selected,&$ordered,&$visiting,&$done) {
            if (isset($done[$id]) || !isset($selected[$id])) { return; }
            // Assets are an enhancement layer. A bad third-party dependency graph must
            // never make the storefront or admin unavailable. Break only the cyclic edge
            // and keep deterministic registration order for the affected asset.
            if (isset($visiting[$id])) { return; }
            $visiting[$id]=true;
            foreach ($selected[$id]['dependencies'] as $dep) {
                if ($dep !== $id && !isset($visiting[$dep])) { $visit($dep); }
            }
            unset($visiting[$id]); $done[$id]=true; $ordered[$id]=$selected[$id];
        };
        $ids=array_keys($selected); usort($ids,function($a,$b)use($selected){$pa=$selected[$a]['priority'];$pb=$selected[$b]['priority'];return $pa===$pb?strcmp($a,$b):($pa<=>$pb);});
        foreach($ids as $id){$visit($id);} return $ordered;
    }

	public function setOgImage($image) {
		$this->og_image = $image;
	}

	public function getOgImage() {
		return $this->og_image;
	}

	public function setOgType($type) {
		$type = strtolower(trim((string)$type));
		$this->og_type = preg_match('/^[a-z][a-z0-9._:-]{0,63}$/', $type) ? $type : 'website';
	}

	public function getOgType() {
		return $this->og_type ?: 'website';
	}
}
