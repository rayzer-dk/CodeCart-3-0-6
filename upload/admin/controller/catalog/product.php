<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCatalogProduct extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		$this->getList();
	}

	public function add() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$allowed_tax_display_modes = array('inherit','native','none','gross_net','gross_tax','net_gross');
			$tax_display_mode = isset($this->request->post['tax_display_mode']) ? (string)$this->request->post['tax_display_mode'] : 'inherit';
			$this->request->post['tax_display_mode'] = in_array($tax_display_mode, $allowed_tax_display_modes, true) ? $tax_display_mode : 'inherit';

			$this->normalizePurchaseBlocksPost();

			$product_id = (int)$this->model_catalog_product->addProduct($this->request->post);
			$this->queueRelationRefresh($product_id);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_model'])) {
				$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_price'])) {
				$url .= '&filter_price=' . $this->request->get['filter_price'];
			}
			
			if (isset($this->request->get['filter_price_min'])) {
				$url .= '&filter_price_min=' . $this->request->get['filter_price_min'];
			}
			
			if (isset($this->request->get['filter_price_max'])) {
				$url .= '&filter_price_max=' . $this->request->get['filter_price_max'];
			}

			if (isset($this->request->get['filter_quantity'])) {
				$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
			}
			
			if (isset($this->request->get['filter_quantity_min'])) {
				$url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
			}
			
			if (isset($this->request->get['filter_quantity_max'])) {
				$url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
			}

			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category'])) {
				$url .= '&filter_category=' . $this->request->get['filter_category'];
				if (isset($this->request->get['filter_sub_category'])) {
					$url .= '&filter_sub_category';
				}
			}
			
			if (isset($this->request->get['filter_manufacturer_id'])) {
				$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
			}
			
			if (isset($this->request->get['filter_noindex'])) {
				$url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
			}

			if (isset($this->request->get['filter_attribute_id'])) {
				$url .= '&filter_attribute_id=' . (int)$this->request->get['filter_attribute_id'];
			}

			if (isset($this->request->get['filter_option_id'])) {
				$url .= '&filter_option_id=' . (int)$this->request->get['filter_option_id'];
			}

			if (isset($this->request->get['filter_download_id'])) {
				$url .= '&filter_download_id=' . (int)$this->request->get['filter_download_id'];
			}

			if (isset($this->request->get['filter_seo_url']) && $this->request->get['filter_seo_url'] !== '') {
				$url .= '&filter_seo_url=' . urlencode((string)$this->request->get['filter_seo_url']);
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$allowed_tax_display_modes = array('inherit','native','none','gross_net','gross_tax','net_gross');
			$tax_display_mode = isset($this->request->post['tax_display_mode']) ? (string)$this->request->post['tax_display_mode'] : 'inherit';
			$this->request->post['tax_display_mode'] = in_array($tax_display_mode, $allowed_tax_display_modes, true) ? $tax_display_mode : 'inherit';

			$this->normalizePurchaseBlocksPost();

			$this->model_catalog_product->editProduct($this->request->get['product_id'], $this->request->post);
			$this->queueRelationRefresh((int)$this->request->get['product_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_model'])) {
				$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_price'])) {
				$url .= '&filter_price=' . $this->request->get['filter_price'];
			}
			
			if (isset($this->request->get['filter_price_min'])) {
				$url .= '&filter_price_min=' . $this->request->get['filter_price_min'];
			}
			
			if (isset($this->request->get['filter_price_max'])) {
				$url .= '&filter_price_max=' . $this->request->get['filter_price_max'];
			}

			if (isset($this->request->get['filter_quantity'])) {
				$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
			}
			
			if (isset($this->request->get['filter_quantity_min'])) {
				$url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
			}
			
			if (isset($this->request->get['filter_quantity_max'])) {
				$url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
			}

			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category'])) {
				$url .= '&filter_category=' . $this->request->get['filter_category'];
				if (isset($this->request->get['filter_sub_category'])) {
					$url .= '&filter_sub_category';
				}
			}
			
			if (isset($this->request->get['filter_manufacturer_id'])) {
				$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
			}
			
			if (isset($this->request->get['filter_noindex'])) {
				$url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
			}

			if (isset($this->request->get['filter_attribute_id'])) {
				$url .= '&filter_attribute_id=' . (int)$this->request->get['filter_attribute_id'];
			}

			if (isset($this->request->get['filter_option_id'])) {
				$url .= '&filter_option_id=' . (int)$this->request->get['filter_option_id'];
			}

			if (isset($this->request->get['filter_download_id'])) {
				$url .= '&filter_download_id=' . (int)$this->request->get['filter_download_id'];
			}

			if (isset($this->request->get['filter_seo_url']) && $this->request->get['filter_seo_url'] !== '') {
				$url .= '&filter_seo_url=' . urlencode((string)$this->request->get['filter_seo_url']);
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->deleteProduct($product_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_model'])) {
				$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_price'])) {
				$url .= '&filter_price=' . $this->request->get['filter_price'];
			}
			
			if (isset($this->request->get['filter_price_min'])) {
				$url .= '&filter_price_min=' . $this->request->get['filter_price_min'];
			}
			
			if (isset($this->request->get['filter_price_max'])) {
				$url .= '&filter_price_max=' . $this->request->get['filter_price_max'];
			}

			if (isset($this->request->get['filter_quantity'])) {
				$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
			}
			
			if (isset($this->request->get['filter_quantity_min'])) {
				$url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
			}
			
			if (isset($this->request->get['filter_quantity_max'])) {
				$url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
			}

			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category'])) {
				$url .= '&filter_category=' . $this->request->get['filter_category'];
				if (isset($this->request->get['filter_sub_category'])) {
					$url .= '&filter_sub_category';
				}
			}
			
			if (isset($this->request->get['filter_manufacturer_id'])) {
				$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
			}
			
			if (isset($this->request->get['filter_noindex'])) {
				$url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
			}

			if (isset($this->request->get['filter_attribute_id'])) {
				$url .= '&filter_attribute_id=' . (int)$this->request->get['filter_attribute_id'];
			}

			if (isset($this->request->get['filter_option_id'])) {
				$url .= '&filter_option_id=' . (int)$this->request->get['filter_option_id'];
			}

			if (isset($this->request->get['filter_download_id'])) {
				$url .= '&filter_download_id=' . (int)$this->request->get['filter_download_id'];
			}

			if (isset($this->request->get['filter_seo_url']) && $this->request->get['filter_seo_url'] !== '') {
				$url .= '&filter_seo_url=' . urlencode((string)$this->request->get['filter_seo_url']);
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function copy() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateCopy()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->copyProduct($product_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_model'])) {
				$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_price'])) {
				$url .= '&filter_price=' . $this->request->get['filter_price'];
			}
			
			if (isset($this->request->get['filter_price_min'])) {
				$url .= '&filter_price_min=' . $this->request->get['filter_price_min'];
			}
			
			if (isset($this->request->get['filter_price_max'])) {
				$url .= '&filter_price_max=' . $this->request->get['filter_price_max'];
			}

			if (isset($this->request->get['filter_quantity'])) {
				$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
			}
			
			if (isset($this->request->get['filter_quantity_min'])) {
				$url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
			}
			
			if (isset($this->request->get['filter_quantity_max'])) {
				$url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
			}

			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category'])) {
				$url .= '&filter_category=' . $this->request->get['filter_category'];
				if (isset($this->request->get['filter_sub_category'])) {
					$url .= '&filter_sub_category';
				}
			}
			
			if (isset($this->request->get['filter_manufacturer_id'])) {
				$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
			}
			
			if (isset($this->request->get['filter_noindex'])) {
				$url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
			}

			if (isset($this->request->get['filter_attribute_id'])) {
				$url .= '&filter_attribute_id=' . (int)$this->request->get['filter_attribute_id'];
			}

			if (isset($this->request->get['filter_option_id'])) {
				$url .= '&filter_option_id=' . (int)$this->request->get['filter_option_id'];
			}

			if (isset($this->request->get['filter_download_id'])) {
				$url .= '&filter_download_id=' . (int)$this->request->get['filter_download_id'];
			}

			if (isset($this->request->get['filter_seo_url']) && $this->request->get['filter_seo_url'] !== '') {
				$url .= '&filter_seo_url=' . urlencode((string)$this->request->get['filter_seo_url']);
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} else {
			$filter_model = '';
		}

		if (isset($this->request->get['filter_price'])) {
			$filter_price = $this->request->get['filter_price'];
		} else {
			$filter_price = '';
		}
		
		if (isset($this->request->get['filter_price_min'])) {
			$filter_price_min = $this->request->get['filter_price_min'];
		} else {
			$filter_price_min = null;
		}
		
		if (isset($this->request->get['filter_price_max'])) {
			$filter_price_max = $this->request->get['filter_price_max'];
		} else {
			$filter_price_max = null;
		}

		if (isset($this->request->get['filter_quantity'])) {
			$filter_quantity = $this->request->get['filter_quantity'];
		} else {
			$filter_quantity = '';
		}
		
		if (isset($this->request->get['filter_quantity_min'])) {
			$filter_quantity_min = $this->request->get['filter_quantity_min'];
		} else {
			$filter_quantity_min = null;
		}
		
		if (isset($this->request->get['filter_quantity_max'])) {
			$filter_quantity_max = $this->request->get['filter_quantity_max'];
		} else {
			$filter_quantity_max = null;
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}
		
		$filter_sub_category = null;
		if (isset($this->request->get['filter_category'])) {
			$filter_category = $this->request->get['filter_category'];
			if (!empty($filter_category) && isset($this->request->get['filter_sub_category'])) {
				$filter_sub_category = true;
			} elseif (isset($this->request->get['filter_sub_category'])) {
				unset($this->request->get['filter_sub_category']);
			}
		} else {
			$filter_category = null;
			if (isset($this->request->get['filter_sub_category'])) {
				unset($this->request->get['filter_sub_category']);
			}
		}
		
		$filter_category_name = null;
		if (isset($filter_category)) {
			if ($filter_category>0) {
				$this->load->model('catalog/category');
				

				
				$category = $this->model_catalog_category->getCategory($filter_category);
				if ($category) {
					$filter_category_name = ($category['path']) ? $category['path'] . ' &gt; ' . $category['name'] : $category['name'];
				} else {
					$filter_category = null;
					unset($this->request->get['filter_category']);
					$filter_sub_category = null;
					if (isset($this->request->get['filter_sub_category'])) {
						unset($this->request->get['filter_sub_category']);
					}
				}
			} else {
				$filter_category_name = $this->language->get('text_none_category');
			}
		}
		
		$filter_manufacturer_id = null;
		$filter_manufacturer_name = '';
		if (isset($this->request->get['filter_manufacturer_id'])) {
			$filter_manufacturer_id = (int)$this->request->get['filter_manufacturer_id'];
			if($filter_manufacturer_id > 0) {
				$this->load->model('catalog/manufacturer');
				$manufacturer = $this->model_catalog_manufacturer->getManufacturer($filter_manufacturer_id);
				if ($manufacturer) {
					$filter_manufacturer_name = $manufacturer['name'];
				} else {
					$filter_manufacturer_name = null;
					unset($this->request->get['filter_manufacturer_id']);
				}
			} else {
				$filter_manufacturer_id = 0;
				$filter_manufacturer_name =  $this->language->get('text_none_manufacturer');
			}
		}
		
		/*
		$filter_manufacturer_name = null;
		if (isset($filter_manufacturer)) {
			if ($filter_manufacturer>0) {
				$this->load->model('catalog/manufacturer');
				$manufacturer = $this->model_catalog_manufacturer->getManufacturer($filter_manufacturer);
				if ($manufacturer) {
					$filter_manufacturer_name = $manufacturer['name'];
				} else {
					$filter_manufacturer = null;
					unset($this->request->get['filter_manufacturer']);
				}
			} elseif ($filter_manufacturer==0) {
				$filter_manufacturer_name = $this->language->get('text_none_manufacturer');
			}
		}
		*/
		
		if (isset($this->request->get['filter_noindex'])) {
			$filter_noindex = $this->request->get['filter_noindex'];
		} else {
			$filter_noindex = '';
		}

		$filter_attribute_id = isset($this->request->get['filter_attribute_id']) ? (int)$this->request->get['filter_attribute_id'] : 0;
		$filter_attribute_name = '';
		if ($filter_attribute_id > 0) {
			$this->load->model('catalog/attribute');
			$attribute_info = $this->model_catalog_attribute->getAttribute($filter_attribute_id);
			if ($attribute_info) { $filter_attribute_name = $attribute_info['name']; } else { $filter_attribute_id = 0; }
		}

		$filter_option_id = isset($this->request->get['filter_option_id']) ? (int)$this->request->get['filter_option_id'] : 0;
		$filter_option_name = '';
		if ($filter_option_id > 0) {
			$this->load->model('catalog/option');
			$option_info = $this->model_catalog_option->getOption($filter_option_id);
			if ($option_info) { $filter_option_name = $option_info['name']; } else { $filter_option_id = 0; }
		}

		$filter_download_id = isset($this->request->get['filter_download_id']) ? (int)$this->request->get['filter_download_id'] : 0;
		$filter_download_name = '';
		if ($filter_download_id > 0) {
			$this->load->model('catalog/download');
			$download_info = $this->model_catalog_download->getDownload($filter_download_id);
			if ($download_info) { $filter_download_name = $download_info['name']; } else { $filter_download_id = 0; }
		}

		$filter_seo_url = isset($this->request->get['filter_seo_url']) ? trim((string)$this->request->get['filter_seo_url']) : '';

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'pd.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}
		
		if (isset($this->request->get['filter_price_min'])) {
			$url .= '&filter_price_min=' . $this->request->get['filter_price_min'];
		}
		
		if (isset($this->request->get['filter_price_max'])) {
			$url .= '&filter_price_max=' . $this->request->get['filter_price_max'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}
		
		if (isset($this->request->get['filter_quantity_min'])) {
			$url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
		}
		
		if (isset($this->request->get['filter_quantity_max'])) {
			$url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
		
		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
			if (isset($this->request->get['filter_sub_category'])) {
				$url .= '&filter_sub_category';
			}
		}
		
		
		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}
		
		if (isset($this->request->get['filter_noindex'])) {
			$url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
		}

		if (isset($this->request->get['filter_attribute_id'])) { $url .= '&filter_attribute_id=' . (int)$this->request->get['filter_attribute_id']; }
		if (isset($this->request->get['filter_option_id'])) { $url .= '&filter_option_id=' . (int)$this->request->get['filter_option_id']; }
		if (isset($this->request->get['filter_download_id'])) { $url .= '&filter_download_id=' . (int)$this->request->get['filter_download_id']; }
		if (isset($this->request->get['filter_seo_url']) && $this->request->get['filter_seo_url'] !== '') { $url .= '&filter_seo_url=' . urlencode((string)$this->request->get['filter_seo_url']); }

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['copy'] = $this->url->link('catalog/product/copy', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['relation_queue_selected'] = $this->url->link('catalog/relation/queueSelected', 'user_token=' . $this->session->data['user_token'], true);
		$data['codecart_relation_status'] = (bool)$this->config->get('codecart_relation_status') && $this->user->hasPermission('modify', 'catalog/relation');
		$data['button_relation_selected'] = $this->language->get('button_relation_selected');
		$data['delete'] = $this->url->link('catalog/product/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['enabled'] = $this->url->link('catalog/product/enable', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['disabled'] = $this->url->link('catalog/product/disable', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['extra_tab_global'] = $this->url->link('catalog/product/extraTabGlobal', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['extra_tab_hidden'] = $this->url->link('catalog/product/extraTabHidden', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['products'] = array();

		$filter_data = array(
			'filter_name'	  => $filter_name,
			'filter_model'	  => $filter_model,
			'filter_price'	  => $filter_price,
			'filter_price_min'=> $filter_price_min,
			'filter_price_max'=> $filter_price_max,
			'filter_quantity' => $filter_quantity,
			'filter_quantity_min' 	=> $filter_quantity_min,
			'filter_quantity_max' 	=> $filter_quantity_max,
			'filter_status'   		=> $filter_status,
			'filter_category'		=> $filter_category,
			'filter_sub_category'	=> $filter_sub_category,
			'filter_manufacturer_id'=> $filter_manufacturer_id,
			'filter_noindex' 		=> $filter_noindex,
			'filter_attribute_id'  => $filter_attribute_id,
			'filter_option_id'     => $filter_option_id,
			'filter_download_id'   => $filter_download_id,
			'filter_seo_url'       => $filter_seo_url,
			'sort'            		=> $sort,
			'order'           		=> $order,
			'start'           		=> ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           		=> $this->config->get('config_limit_admin')
		);


		$this->load->model('tool/image');

		$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

		$results = $this->model_catalog_product->getProducts($filter_data);
		$product_ids = array();
		foreach ($results as $result) {
			$product_ids[] = (int)$result['product_id'];
		}
		$specials_by_product = $this->model_catalog_product->getProductSpecialsByProductIds($product_ids);
		$stock_notify_counts = $this->model_catalog_product->getStockNotifyCountsByProductIds($product_ids);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.webp', 40, 40);
			}

			$special = false;

			$product_specials = isset($specials_by_product[(int)$result['product_id']]) ? $specials_by_product[(int)$result['product_id']] : array();

			foreach ($product_specials as $product_special) {
				if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
					$special = $this->currency->format($product_special['price'], $this->config->get('config_currency'));

					break;
				}
			}

			$data['products'][] = array(
				'product_id' => $result['product_id'],
				'image'      => $image,
				'name'       => $result['name'],
				'model'      => $result['model'],
				'price'      => $this->currency->format($result['price'], $this->config->get('config_currency')),
				'special'    => $special,
				'quantity'   => $result['quantity'],
				'stock_notify_count' => isset($stock_notify_counts[(int)$result['product_id']]) ? $stock_notify_counts[(int)$result['product_id']] : 0,
				'status'     => $result['status'] ? $this->language->get('text_enabled_short') : $this->language->get('text_disabled_short'),
				'noindex'    => $result['noindex'] ? $this->language->get('text_enabled_short') : $this->language->get('text_disabled_short'),
				'href_shop'  => HTTP_CATALOG . 'index.php?route=product/product&product_id=' . $result['product_id'],
				'edit'       => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true)
			);
		}

		$data['text_stock_notify_waiting'] = $this->language->get('text_stock_notify_waiting');

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}
		
		if (isset($this->request->get['filter_price_min'])) {
			$url .= '&filter_price_min=' . $this->request->get['filter_price_min'];
		}
		
		if (isset($this->request->get['filter_price_max'])) {
			$url .= '&filter_price_max=' . $this->request->get['filter_price_max'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}
		
		if (isset($this->request->get['filter_quantity_min'])) {
			$url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
		}
		
		if (isset($this->request->get['filter_quantity_max'])) {
			$url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
		
		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
			if (isset($this->request->get['filter_sub_category'])) {
				$url .= '&filter_sub_category';
			}
		}
		
		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}
		
		if (isset($this->request->get['filter_noindex'])) {
			$url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
		}

		if (isset($this->request->get['filter_attribute_id'])) { $url .= '&filter_attribute_id=' . (int)$this->request->get['filter_attribute_id']; }
		if (isset($this->request->get['filter_option_id'])) { $url .= '&filter_option_id=' . (int)$this->request->get['filter_option_id']; }
		if (isset($this->request->get['filter_download_id'])) { $url .= '&filter_download_id=' . (int)$this->request->get['filter_download_id']; }
		if (isset($this->request->get['filter_seo_url']) && $this->request->get['filter_seo_url'] !== '') { $url .= '&filter_seo_url=' . urlencode((string)$this->request->get['filter_seo_url']); }

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, true);
		$data['sort_model'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, true);
		$data['sort_price'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url, true);
		$data['sort_quantity'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url, true);
		$data['sort_status'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, true);
		$data['sort_noindex'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.noindex' . $url, true);
		$data['sort_order'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}
		
		if (isset($this->request->get['filter_price_min'])) {
			$url .= '&filter_price_min=' . $this->request->get['filter_price_min'];
		}
		
		if (isset($this->request->get['filter_price_max'])) {
			$url .= '&filter_price_max=' . $this->request->get['filter_price_max'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}
		
		if (isset($this->request->get['filter_quantity_min'])) {
			$url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
		}
		
		if (isset($this->request->get['filter_quantity_max'])) {
			$url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
		
		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
			if (isset($this->request->get['filter_sub_category'])) {
				$url .= '&filter_sub_category';
			}
		}
		
		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}
		
		if (isset($this->request->get['filter_noindex'])) {
			$url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
		}

		if (isset($this->request->get['filter_attribute_id'])) { $url .= '&filter_attribute_id=' . (int)$this->request->get['filter_attribute_id']; }
		if (isset($this->request->get['filter_option_id'])) { $url .= '&filter_option_id=' . (int)$this->request->get['filter_option_id']; }
		if (isset($this->request->get['filter_download_id'])) { $url .= '&filter_download_id=' . (int)$this->request->get['filter_download_id']; }
		if (isset($this->request->get['filter_seo_url']) && $this->request->get['filter_seo_url'] !== '') { $url .= '&filter_seo_url=' . urlencode((string)$this->request->get['filter_seo_url']); }

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

		$data['filter_name'] = $filter_name;
		$data['filter_model'] = $filter_model;
		$data['filter_price'] = $filter_price;
		$data['filter_price_min'] = $filter_price_min;
		$data['filter_price_max'] = $filter_price_max;
		$data['filter_quantity'] = $filter_quantity;
		$data['filter_quantity_min'] = $filter_quantity_min;
		$data['filter_quantity_max'] = $filter_quantity_max;
		$data['filter_status'] = $filter_status;
		$data['filter_category_name'] = $filter_category_name;
		$data['filter_category'] = $filter_category;
		$data['filter_sub_category'] = $filter_sub_category;
		$data['filter_manufacturer_name'] = $filter_manufacturer_name;
		$data['filter_manufacturer_id'] = $filter_manufacturer_id;
		$data['filter_noindex'] = $filter_noindex;
		$data['filter_attribute_id'] = $filter_attribute_id;
		$data['filter_attribute_name'] = $filter_attribute_name;
		$data['filter_option_id'] = $filter_option_id;
		$data['filter_option_name'] = $filter_option_name;
		$data['filter_download_id'] = $filter_download_id;
		$data['filter_download_name'] = $filter_download_name;
		$data['filter_seo_url'] = $filter_seo_url;
		$data['active_filter_count'] = count(array_filter(array($filter_name, $filter_model, $filter_price_min, $filter_price_max, $filter_quantity_min, $filter_quantity_max, ($filter_status === '' ? '' : '1'), ($filter_category === null ? '' : '1'), $filter_manufacturer_id ? '1' : '', ($filter_noindex === '' ? '' : '1'), $filter_attribute_id, $filter_option_id, $filter_download_id, $filter_seo_url), function($value) { return $value !== '' && $value !== null && $value !== 0; }));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product_list', $data));
	}

	private function normalizePurchaseBlocksPost() {
		$blocks = isset($this->request->post['purchase_blocks_json']) && is_array($this->request->post['purchase_blocks_json']) ? $this->request->post['purchase_blocks_json'] : array();
		$modeWasPosted = isset($this->request->post['purchase_block_mode']);
		$mode = $modeWasPosted ? (string)$this->request->post['purchase_block_mode'] : 'inherit';
		if (!in_array($mode, array('inherit', 'custom', 'disabled'), true)) { $mode = 'inherit'; }
		if (!$modeWasPosted && !empty($this->request->post['purchase_blocks_present'])) {
			foreach ($blocks as $candidateJson) {
				if (\CodeCart\Core\PurchaseBlocks::decode($candidateJson)) { $mode = 'custom'; break; }
			}
		}
		$this->request->post['purchase_block_mode'] = $mode;
		if ($mode !== 'custom') {
			$this->request->post['purchase_blocks_json'] = array();
			return;
		}
		$clean = array();
		foreach ($blocks as $language_id => $json) {
			$language_id = (int)$language_id;
			if ($language_id > 0) { $clean[$language_id] = \CodeCart\Core\PurchaseBlocks::encode($json); }
		}
		$this->request->post['purchase_blocks_json'] = $clean;
	}

	protected function getForm() {
		$data['button_generate_seo_url'] = $this->language->get('button_generate_seo_url');
		$data['text_form'] = !isset($this->request->get['product_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}
		
		if (isset($this->error['meta_h1'])) {
			$data['error_meta_h1'] = $this->error['meta_h1'];
		} else {
			$data['error_meta_h1'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}
		
		if (isset($this->request->get['filter_price_min'])) {
			$url .= '&filter_price_min=' . $this->request->get['filter_price_min'];
		}
		
		if (isset($this->request->get['filter_price_max'])) {
			$url .= '&filter_price_max=' . $this->request->get['filter_price_max'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}
		
		if (isset($this->request->get['filter_quantity_min'])) {
			$url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
		}
		
		if (isset($this->request->get['filter_quantity_max'])) {
			$url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
		
		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
			if (isset($this->request->get['filter_sub_category'])) {
				$url .= '&filter_sub_category';
			}
		}
		
		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}
		
		if (isset($this->request->get['filter_noindex'])) {
			$url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
		}

		if (isset($this->request->get['filter_attribute_id'])) { $url .= '&filter_attribute_id=' . (int)$this->request->get['filter_attribute_id']; }
		if (isset($this->request->get['filter_option_id'])) { $url .= '&filter_option_id=' . (int)$this->request->get['filter_option_id']; }
		if (isset($this->request->get['filter_download_id'])) { $url .= '&filter_download_id=' . (int)$this->request->get['filter_download_id']; }
		if (isset($this->request->get['filter_seo_url']) && $this->request->get['filter_seo_url'] !== '') { $url .= '&filter_seo_url=' . urlencode((string)$this->request->get['filter_seo_url']); }

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['product_id'])) {
			$data['action'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $this->request->get['product_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->document->addStyle('view/stylesheet/codecart-purchase-blocks.css?v=3.0.6.0');
		$this->document->addScript('view/javascript/codecart-purchase-blocks.js?v=3.0.6.0');
		$this->load->model('design/form');
		$data['available_forms'] = $this->model_design_form->getFormOptions();
		$data['purchase_blocks_master_enabled'] = (bool)$this->config->get('theme_default_purchase_blocks_status');
		$data['purchase_blocks_settings_url'] = $this->url->link('extension/theme/codecart', 'user_token=' . $this->session->data['user_token'] . '&store_id=0', true);
		$data['purchase_blocks_forms_url'] = $this->url->link('design/form', 'user_token=' . $this->session->data['user_token'], true);
		$data['purchase_block_scope'] = 'product';
		if (isset($this->request->post['purchase_block_mode'])) {
			$data['purchase_block_mode'] = (string)$this->request->post['purchase_block_mode'];
			$data['purchase_blocks_json'] = isset($this->request->post['purchase_blocks_json']) && is_array($this->request->post['purchase_blocks_json']) ? $this->request->post['purchase_blocks_json'] : array();
		} else {
			$owner_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
			$saved_purchase_blocks = $owner_id ? $this->model_catalog_product->getPurchaseBlocks('product', $owner_id) : array('mode'=>'inherit','languages'=>array());
			$data['purchase_block_mode'] = isset($saved_purchase_blocks['mode']) ? (string)$saved_purchase_blocks['mode'] : 'inherit';
			$data['purchase_blocks_json'] = isset($saved_purchase_blocks['languages']) && is_array($saved_purchase_blocks['languages']) ? $saved_purchase_blocks['languages'] : array();
		}
		if (!in_array($data['purchase_block_mode'], array('inherit','custom','disabled'), true)) { $data['purchase_block_mode'] = 'inherit'; }
		foreach (array('text_purchase_block_templates', 'text_purchase_block_preset_delivery', 'text_purchase_block_preset_instruction', 'text_purchase_block_preset_rules', 'text_purchase_block_preset_shoes_eu', 'text_purchase_block_preset_women_eu', 'text_purchase_block_preset_men_eu', 'text_purchase_block_info', 'text_purchase_block_size_table', 'text_purchase_block_sizes', 'text_purchase_block_colors', 'text_purchase_block_form', 'text_purchase_block_empty', 'text_purchase_block_builder', 'text_purchase_block_inherit', 'text_purchase_block_custom', 'text_purchase_block_disabled', 'entry_purchase_block_title', 'entry_purchase_block_content', 'entry_purchase_block_columns', 'entry_purchase_block_rows', 'entry_purchase_block_item_label', 'entry_purchase_block_item_value', 'entry_purchase_block_mode', 'entry_purchase_block_form', 'entry_purchase_block_form_display', 'entry_purchase_block_form_button_text', 'entry_purchase_block_form_icon', 'entry_purchase_block_form_bg', 'entry_purchase_block_form_text_color', 'entry_purchase_block_form_hover_bg', 'entry_purchase_block_enabled', 'button_purchase_block_add_existing_form', 'text_purchase_block_choose_form', 'button_purchase_block_add_item', 'button_purchase_block_up', 'button_purchase_block_down', 'button_purchase_block_add_info', 'button_purchase_block_add_size_table', 'button_purchase_block_add_sizes', 'button_purchase_block_add_colors', 'button_purchase_block_add_form', 'button_remove', 'text_purchase_block_form_inline', 'text_purchase_block_form_button', 'warning_purchase_blocks_master', 'button_purchase_blocks_settings', 'button_purchase_blocks_forms', 'help_purchase_block_mode', 'help_purchase_block_inheritance', 'help_purchase_block_builder') as $purchase_language_key) {
			$data[$purchase_language_key] = $this->language->get($purchase_language_key);
		}

		foreach ($data['languages'] as $purchase_language) {
			$purchase_language_id = (int)$purchase_language['language_id'];
			if (!isset($data['purchase_blocks_json'][$purchase_language_id])) { $data['purchase_blocks_json'][$purchase_language_id] = '[]'; }
		}
		$data['purchase_blocks_html'] = $this->load->view('common/codecart_purchase_blocks', $data);

		if (isset($this->request->post['product_description'])) {
			$data['product_description'] = $this->request->post['product_description'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_description'] = $this->model_catalog_product->getProductDescriptions($this->request->get['product_id']);
		} else {
			$data['product_description'] = array();
		}

		if (isset($this->request->post['product_extra_tab'])) {
			$data['product_extra_tab'] = $this->request->post['product_extra_tab'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_extra_tab'] = $this->model_catalog_product->getProductExtraTab((int)$this->request->get['product_id']);
		} else { $data['product_extra_tab'] = array(); }
		if (isset($this->request->post['product_extra_tab_mode'])) {
			$data['product_extra_tab_mode'] = (int)$this->request->post['product_extra_tab_mode'];
		} elseif (!empty($data['product_extra_tab'])) {
			$first_extra_tab = reset($data['product_extra_tab']);
			$data['product_extra_tab_mode'] = isset($first_extra_tab['mode']) ? (int)$first_extra_tab['mode'] : 0;
		} else { $data['product_extra_tab_mode'] = 0; }
		
		$language_id = $this->config->get('config_language_id');
		if (isset($data['product_description'][$language_id]['name'])) {
			$data['heading_title'] = $data['product_description'][$language_id]['name'];
		}

		if (isset($this->request->post['model'])) {
			$data['model'] = $this->request->post['model'];
		} elseif (!empty($product_info)) {
			$data['model'] = $product_info['model'];
		} else {
			$data['model'] = '';
		}

		if (isset($this->request->post['sku'])) {
			$data['sku'] = $this->request->post['sku'];
		} elseif (!empty($product_info)) {
			$data['sku'] = $product_info['sku'];
		} else {
			$data['sku'] = '';
		}

		if (isset($this->request->post['upc'])) {
			$data['upc'] = $this->request->post['upc'];
		} elseif (!empty($product_info)) {
			$data['upc'] = $product_info['upc'];
		} else {
			$data['upc'] = '';
		}

		if (isset($this->request->post['ean'])) {
			$data['ean'] = $this->request->post['ean'];
		} elseif (!empty($product_info)) {
			$data['ean'] = $product_info['ean'];
		} else {
			$data['ean'] = '';
		}

		if (isset($this->request->post['jan'])) {
			$data['jan'] = $this->request->post['jan'];
		} elseif (!empty($product_info)) {
			$data['jan'] = $product_info['jan'];
		} else {
			$data['jan'] = '';
		}

		if (isset($this->request->post['isbn'])) {
			$data['isbn'] = $this->request->post['isbn'];
		} elseif (!empty($product_info)) {
			$data['isbn'] = $product_info['isbn'];
		} else {
			$data['isbn'] = '';
		}

		if (isset($this->request->post['mpn'])) {
			$data['mpn'] = $this->request->post['mpn'];
		} elseif (!empty($product_info)) {
			$data['mpn'] = $product_info['mpn'];
		} else {
			$data['mpn'] = '';
		}

		if (isset($this->request->post['google_product_category_id'])) {
			$data['google_product_category_id'] = preg_replace('/[^0-9]/', '', (string)$this->request->post['google_product_category_id']);
		} elseif (!empty($product_info) && isset($product_info['google_product_category_id'])) {
			$data['google_product_category_id'] = preg_replace('/[^0-9]/', '', (string)$product_info['google_product_category_id']);
		} else {
			$data['google_product_category_id'] = '';
		}

		if (isset($this->request->post['location'])) {
			$data['location'] = $this->request->post['location'];
		} elseif (!empty($product_info)) {
			$data['location'] = $product_info['location'];
		} else {
			$data['location'] = '';
		}

		$this->load->model('setting/store');

		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}

		if (isset($this->request->post['product_store'])) {
			$data['product_store'] = $this->request->post['product_store'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_store'] = $this->model_catalog_product->getProductStores($this->request->get['product_id']);
		} else {
			$data['product_store'] = array(0);
		}

		if (isset($this->request->post['shipping'])) {
			$data['shipping'] = $this->request->post['shipping'];
		} elseif (!empty($product_info)) {
			$data['shipping'] = $product_info['shipping'];
		} else {
			$data['shipping'] = 1;
		}

		if (isset($this->request->post['price'])) {
			$data['price'] = $this->request->post['price'];
		} elseif (!empty($product_info)) {
			$data['price'] = $product_info['price'];
		} else {
			$data['price'] = '';
		}

		$this->load->model('catalog/recurring');

		$data['recurrings'] = $this->model_catalog_recurring->getRecurrings();

		if (isset($this->request->post['product_recurrings'])) {
			$data['product_recurrings'] = $this->request->post['product_recurrings'];
		} elseif (!empty($product_info)) {
			$data['product_recurrings'] = $this->model_catalog_product->getRecurrings($product_info['product_id']);
		} else {
			$data['product_recurrings'] = array();
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['tax_class_id'])) {
			$data['tax_class_id'] = $this->request->post['tax_class_id'];
		} elseif (!empty($product_info)) {
			$data['tax_class_id'] = $product_info['tax_class_id'];
		} else {
			$data['tax_class_id'] = 0;
		}

		if (isset($this->request->post['tax_display_mode'])) {
			$data['tax_display_mode'] = (string)$this->request->post['tax_display_mode'];
		} elseif (!empty($product_info) && isset($product_info['tax_display_mode'])) {
			$data['tax_display_mode'] = (string)$product_info['tax_display_mode'];
		} else {
			$data['tax_display_mode'] = 'inherit';
		}
		if (!in_array($data['tax_display_mode'], array('inherit','native','none','gross_net','gross_tax','net_gross'), true)) { $data['tax_display_mode'] = 'inherit'; }

		if (isset($this->request->post['date_available'])) {
			$data['date_available'] = $this->request->post['date_available'];
		} elseif (!empty($product_info)) {
			$data['date_available'] = ($product_info['date_available'] != '0000-00-00' && $product_info['date_available'] != '1970-01-01') ? $product_info['date_available'] : '';
		} else {
			$data['date_available'] = date('Y-m-d');
		}

		if (isset($this->request->post['quantity'])) {
			$data['quantity'] = $this->request->post['quantity'];
		} elseif (!empty($product_info)) {
			$data['quantity'] = $product_info['quantity'];
		} else {
			$data['quantity'] = 1;
		}

		if (isset($this->request->post['minimum'])) {
			$data['minimum'] = $this->request->post['minimum'];
		} elseif (!empty($product_info)) {
			$data['minimum'] = $product_info['minimum'];
		} else {
			$data['minimum'] = 1;
		}

		if (isset($this->request->post['subtract'])) {
			$data['subtract'] = $this->request->post['subtract'];
		} elseif (!empty($product_info)) {
			$data['subtract'] = $product_info['subtract'];
		} else {
			$data['subtract'] = 1;
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($product_info)) {
			$data['sort_order'] = $product_info['sort_order'];
		} else {
			$data['sort_order'] = 1;
		}

		$this->load->model('localisation/stock_status');

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		if (isset($this->request->post['stock_status_id'])) {
			$data['stock_status_id'] = $this->request->post['stock_status_id'];
		} elseif (!empty($product_info)) {
			$data['stock_status_id'] = $product_info['stock_status_id'];
		} else {
			$data['stock_status_id'] = 0;
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($product_info)) {
			$data['status'] = $product_info['status'];
		} else {
			$data['status'] = true;
		}
		
		if (isset($this->request->post['noindex'])) {
			$data['noindex'] = $this->request->post['noindex'];
		} elseif (!empty($product_info)) {
			$data['noindex'] = $product_info['noindex'];
		} else {
			$data['noindex'] = 1;
		}

		if (isset($this->request->post['weight'])) {
			$data['weight'] = $this->request->post['weight'];
		} elseif (!empty($product_info)) {
			$data['weight'] = $product_info['weight'];
		} else {
			$data['weight'] = '';
		}

		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

		if (isset($this->request->post['weight_class_id'])) {
			$data['weight_class_id'] = $this->request->post['weight_class_id'];
		} elseif (!empty($product_info)) {
			$data['weight_class_id'] = $product_info['weight_class_id'];
		} else {
			$data['weight_class_id'] = $this->config->get('config_weight_class_id');
		}

		if (isset($this->request->post['length'])) {
			$data['length'] = $this->request->post['length'];
		} elseif (!empty($product_info)) {
			$data['length'] = $product_info['length'];
		} else {
			$data['length'] = '';
		}

		if (isset($this->request->post['width'])) {
			$data['width'] = $this->request->post['width'];
		} elseif (!empty($product_info)) {
			$data['width'] = $product_info['width'];
		} else {
			$data['width'] = '';
		}

		if (isset($this->request->post['height'])) {
			$data['height'] = $this->request->post['height'];
		} elseif (!empty($product_info)) {
			$data['height'] = $product_info['height'];
		} else {
			$data['height'] = '';
		}

		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		if (isset($this->request->post['length_class_id'])) {
			$data['length_class_id'] = $this->request->post['length_class_id'];
		} elseif (!empty($product_info)) {
			$data['length_class_id'] = $product_info['length_class_id'];
		} else {
			$data['length_class_id'] = $this->config->get('config_length_class_id');
		}

		$this->load->model('catalog/manufacturer');

		if (isset($this->request->post['manufacturer_id'])) {
			$data['manufacturer_id'] = $this->request->post['manufacturer_id'];
		} elseif (!empty($product_info)) {
			$data['manufacturer_id'] = $product_info['manufacturer_id'];
		} else {
			$data['manufacturer_id'] = 0;
		}

		if (isset($this->request->post['manufacturer'])) {
			$data['manufacturer'] = $this->request->post['manufacturer'];
		} elseif (!empty($product_info)) {
			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

			if ($manufacturer_info) {
				$data['manufacturer'] = $manufacturer_info['name'];
			} else {
				$data['manufacturer'] = '';
			}
		} else {
			$data['manufacturer'] = '';
		}

		// Categories
		$this->load->model('catalog/category');
		
		$data['categories'] = $this->model_catalog_category->getAllCategories();
		
		if (isset($this->request->post['main_category_id'])) {
			$data['main_category_id'] = $this->request->post['main_category_id'];
				} elseif (isset($product_info)) {
			$data['main_category_id'] = $this->model_catalog_product->getProductMainCategoryId($this->request->get['product_id']);
				} else {
			$data['main_category_id'] = 0;
		}			

		if (isset($this->request->post['product_category'])) {
			$categories = $this->request->post['product_category'];
		} elseif (isset($this->request->get['product_id'])) {
			$categories = $this->model_catalog_product->getProductCategories($this->request->get['product_id']);
		} else {
			$categories = array();
		}

		$data['product_categories'] = array();

		foreach ($categories as $category_id) {
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$data['product_categories'][] = array(
					'category_id' => $category_info['category_id'],
					'name'        => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
				);
			}
		}

		// Filters
		$this->load->model('catalog/filter');

		if (isset($this->request->post['product_filter'])) {
			$filters = $this->request->post['product_filter'];
		} elseif (isset($this->request->get['product_id'])) {
			$filters = $this->model_catalog_product->getProductFilters($this->request->get['product_id']);
		} else {
			$filters = array();
		}

		$data['product_filters'] = array();

		foreach ($filters as $filter_id) {
			$filter_info = $this->model_catalog_filter->getFilter($filter_id);

			if ($filter_info) {
				$data['product_filters'][] = array(
					'filter_id' => $filter_info['filter_id'],
					'name'      => $filter_info['group'] . ' &gt; ' . $filter_info['name']
				);
			}
		}

		// Attributes
		$this->load->model('catalog/attribute');

		if (isset($this->request->post['product_attribute'])) {
			$product_attributes = $this->request->post['product_attribute'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_attributes = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);
		} else {
			$product_attributes = array();
		}

		$data['product_attributes'] = array();

		foreach ($product_attributes as $product_attribute) {
			$attribute_info = $this->model_catalog_attribute->getAttribute($product_attribute['attribute_id']);

			if ($attribute_info) {
				$data['product_attributes'][] = array(
					'attribute_id'                  => $product_attribute['attribute_id'],
					'name'                          => $attribute_info['name'],
					'product_attribute_description' => $product_attribute['product_attribute_description']
				);
			}
		}

		// Options
		$this->load->model('catalog/option');

		if (isset($this->request->post['product_option'])) {
			$product_options = $this->request->post['product_option'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);
		} else {
			$product_options = array();
		}

		$data['product_options'] = array();

		foreach ($product_options as $product_option) {
			$product_option_value_data = array();

			if (isset($product_option['product_option_value'])) {
				foreach ($product_option['product_option_value'] as $product_option_value) {
					$product_option_value_data[] = array(
						'product_option_value_id' => $product_option_value['product_option_value_id'],
						'option_value_id'         => $product_option_value['option_value_id'],
						'quantity'                => $product_option_value['quantity'],
						'subtract'                => $product_option_value['subtract'],
						'price'                   => $product_option_value['price'],
						'price_prefix'            => $product_option_value['price_prefix'],
						'points'                  => $product_option_value['points'],
						'points_prefix'           => $product_option_value['points_prefix'],
						'weight'                  => $product_option_value['weight'],
						'weight_prefix'           => $product_option_value['weight_prefix']
					);
				}
			}

			$data['product_options'][] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => isset($product_option['value']) ? $product_option['value'] : '',
				'required'             => $product_option['required']
			);
		}

		$data['option_values'] = array();

		foreach ($data['product_options'] as $product_option) {
			if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
				if (!isset($data['option_values'][$product_option['option_id']])) {
					$data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);
				}
			}
		}

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		if (isset($this->request->post['product_discount'])) {
			$product_discounts = $this->request->post['product_discount'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);
		} else {
			$product_discounts = array();
		}

		$data['product_discounts'] = array();

		foreach ($product_discounts as $product_discount) {
			$data['product_discounts'][] = array(
				'customer_group_id' => $product_discount['customer_group_id'],
				'quantity'          => $product_discount['quantity'],
				'priority'          => $product_discount['priority'],
				'price'             => $product_discount['price'],
				'date_start'        => ($product_discount['date_start'] != '0000-00-00' && $product_discount['date_start'] != '1970-01-01') ? $product_discount['date_start'] : '',
				'date_end'          => ($product_discount['date_end'] != '0000-00-00' && $product_discount['date_end'] != '9999-12-31') ? $product_discount['date_end'] : ''
			);
		}

		if (isset($this->request->post['product_special'])) {
			$product_specials = $this->request->post['product_special'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_specials = $this->model_catalog_product->getProductSpecials($this->request->get['product_id']);
		} else {
			$product_specials = array();
		}

		$data['product_specials'] = array();

		foreach ($product_specials as $product_special) {
			$data['product_specials'][] = array(
				'customer_group_id' => $product_special['customer_group_id'],
				'priority'          => $product_special['priority'],
				'price'             => $product_special['price'],
				'date_start'        => ($product_special['date_start'] != '0000-00-00' && $product_special['date_start'] != '1970-01-01') ? $product_special['date_start'] : '',
				'date_end'          => ($product_special['date_end'] != '0000-00-00' && $product_special['date_end'] != '9999-12-31') ? $product_special['date_end'] :  ''
			);
		}

		// Image
		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($product_info)) {
			$data['image'] = $product_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($product_info) && is_file(DIR_IMAGE . $product_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($product_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.webp', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('codecart_add_image.webp', 100, 100);

		// Images
		if (isset($this->request->post['product_image'])) {
			$product_images = $this->request->post['product_image'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_images = $this->model_catalog_product->getProductImages($this->request->get['product_id']);
		} else {
			$product_images = array();
		}

		$data['product_images'] = array();

		foreach ($product_images as $product_image) {
			if (empty($product_image['image'])) {
				continue;
			}

			if (is_file(DIR_IMAGE . $product_image['image'])) {
				$image = $product_image['image'];
				$thumb = $product_image['image'];
			} else {
				$image = '';
				$thumb = 'no_image.webp';
			}

			$data['product_images'][] = array(
				'image'      => $image,
				'thumb'      => $this->model_tool_image->resize($thumb, 100, 100),
				'sort_order' => $product_image['sort_order']
			);
		}

		// Downloads
		$this->load->model('catalog/download');

		if (isset($this->request->post['product_download'])) {
			$product_downloads = $this->request->post['product_download'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_downloads = $this->model_catalog_product->getProductDownloads($this->request->get['product_id']);
		} else {
			$product_downloads = array();
		}

		$data['product_downloads'] = array();

		foreach ($product_downloads as $download_id) {
			$download_info = $this->model_catalog_download->getDownload($download_id);

			if ($download_info) {
				$data['product_downloads'][] = array(
					'download_id' => $download_info['download_id'],
					'name'        => $download_info['name']
				);
			}
		}

		if (isset($this->request->post['product_related'])) {
			$products = $this->request->post['product_related'];
		} elseif (isset($this->request->get['product_id'])) {
			$products = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);
		} else {
			$products = array();
		}

		$data['product_relateds'] = array();

		foreach ($products as $product_id) {
			$related_info = $this->model_catalog_product->getProduct($product_id);

			if ($related_info) {
				$data['product_relateds'][] = array(
					'product_id' => $related_info['product_id'],
					'name'       => $related_info['name']
				);
			}
		}
		
		if (isset($this->request->post['product_related_article'])) {
			$articles = $this->request->post['product_related_article'];
		} elseif (isset($product_info)) {
			$articles = $this->model_catalog_product->getArticleRelated($this->request->get['product_id']);
		} else {
			$articles = array();
		}
	
		$data['product_related_article'] = array();
		$this->load->model('blog/article');
		
		foreach ($articles as $article_id) {
			$article_info = $this->model_blog_article->getArticle($article_id);
			
			if ($article_info) {
				$data['product_related_article'][] = array(
					'article_id' => $article_info['article_id'],
					'name'       => $article_info['name']
				);
			}
		}


        $data['codecart_relation_enabled'] = (bool)$this->config->get('codecart_relation_status') && isset($this->request->get['product_id']) && (int)$this->request->get['product_id'] > 0 && $this->user->hasPermission('access', 'catalog/relation');
        $data['relation_preview_url'] = $data['codecart_relation_enabled'] ? $this->url->link('catalog/relation/preview', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . (int)$this->request->get['product_id'], true) : '';
        $data['button_relation_suggest'] = $this->language->get('button_relation_suggest');
        $data['text_relation_button_help'] = $this->language->get('text_relation_button_help');
        $data['text_relation_autofill_result'] = $this->language->get('text_relation_autofill_result');
        $data['text_relation_selected_count'] = $this->language->get('text_relation_selected_count');
        $data['relation_product_limit'] = max(1, min(24, (int)($this->config->get('codecart_relation_product_limit') ?: 8)));
        $data['relation_article_limit'] = max(0, min(12, (int)($this->config->get('codecart_relation_article_limit') === null ? 3 : $this->config->get('codecart_relation_article_limit'))));
        if ($data['codecart_relation_enabled']) { $this->document->addScript('view/javascript/codecart-relation-picker.js?v=3.0.6.0-121'); }
        $data['button_add_selected'] = $this->language->get('button_add_selected');
        $data['text_relation_products'] = $this->language->get('text_relation_products');
        $data['text_relation_articles'] = $this->language->get('text_relation_articles');
        $data['text_relation_score'] = $this->language->get('text_relation_score');
        $data['text_relation_added'] = $this->language->get('text_relation_added');
        $data['text_relation_button_help'] = $this->language->get('text_relation_button_help');

		if (isset($this->request->post['points'])) {
			$data['points'] = $this->request->post['points'];
		} elseif (!empty($product_info)) {
			$data['points'] = $product_info['points'];
		} else {
			$data['points'] = '';
		}

		if (isset($this->request->post['product_reward'])) {
			$data['product_reward'] = $this->request->post['product_reward'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_reward'] = $this->model_catalog_product->getProductRewards($this->request->get['product_id']);
		} else {
			$data['product_reward'] = array();
		}

		if (isset($this->request->post['product_seo_url'])) {
			$data['product_seo_url'] = $this->request->post['product_seo_url'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_seo_url'] = $this->model_catalog_product->getProductSeoUrls($this->request->get['product_id']);
		} else {
			$data['product_seo_url'] = array();
		}

		if (isset($this->request->post['product_layout'])) {
			$data['product_layout'] = $this->request->post['product_layout'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_layout'] = $this->model_catalog_product->getProductLayouts($this->request->get['product_id']);
		} else {
			$data['product_layout'] = array();
		}

		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		$data['attribute_manager'] = $this->url->link('catalog/attribute', 'user_token=' . $this->session->data['user_token'], true);
		$data['attribute_add'] = $this->url->link('catalog/attribute/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['option_manager'] = $this->url->link('catalog/option', 'user_token=' . $this->session->data['user_token'], true);
		$data['option_add'] = $this->url->link('catalog/option/add', 'user_token=' . $this->session->data['user_token'], true);

		$data['js_purchase_presets_json'] = $this->language->get('js_purchase_presets_json');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['product_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}

			if ((utf8_strlen($value['meta_title']) < 0) || (utf8_strlen($value['meta_title']) > 255)) {
				$this->error['meta_title'][$language_id] = $this->language->get('error_meta_title');
			}
			
						if ((utf8_strlen($value['meta_h1']) < 0) || (utf8_strlen($value['meta_h1']) > 255)) {
				$this->error['meta_h1'][$language_id] = $this->language->get('error_meta_h1');
			}
		}

		$extra_tab_mode = isset($this->request->post['product_extra_tab_mode']) ? (int)$this->request->post['product_extra_tab_mode'] : 0;
		if (!in_array($extra_tab_mode, array(0, 1, 2), true)) { $this->request->post['product_extra_tab_mode'] = 0; }
		if ($extra_tab_mode === 1 && !empty($this->request->post['product_extra_tab']) && is_array($this->request->post['product_extra_tab'])) {
			foreach ($this->request->post['product_extra_tab'] as $extra_tab) {
				$title = isset($extra_tab['title']) ? trim((string)$extra_tab['title']) : '';
				if (utf8_strlen($title) > 128) { $this->error['warning'] = $this->language->get('error_extra_tab_title'); break; }
			}
		}

		if ((utf8_strlen($this->request->post['model']) < 1) || (utf8_strlen($this->request->post['model']) > 64)) {
			$this->error['model'] = $this->language->get('error_model');
		}

		if ($this->request->post['product_seo_url']) {
			$this->load->model('design/seo_url');

			foreach ($this->request->post['product_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {

						$seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);

						foreach ($seo_urls as $seo_url) {
							if (($seo_url['store_id'] == $store_id) && ((int)$seo_url['language_id'] === (int)$language_id) && (!isset($this->request->get['product_id']) || (($seo_url['query'] != 'product_id=' . $this->request->get['product_id'])))) {
								$this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword');

								break;
							}
						}
					}
				}
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}
	
	public function extraTabGlobal() {
		$this->bulkExtraTabMode(0, 'text_extra_tab_bulk_global_success');
	}

	public function extraTabHidden() {
		$this->bulkExtraTabMode(2, 'text_extra_tab_bulk_hidden_success');
	}

	private function bulkExtraTabMode($mode, $success_key) {
		$this->load->language('catalog/product');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateModify()) {
			$selected = array_values(array_unique(array_filter(array_map('intval', (array)$this->request->post['selected']))));
			foreach ($selected as $product_id) {
				$this->model_catalog_product->setProductExtraTabMode($product_id, (int)$mode);
			}
			$this->session->data['success'] = sprintf($this->language->get($success_key), count($selected));
		}

		$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true));
	}

	protected function validateModify() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	public function enable() {
        $this->load->language('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');
        if (isset($this->request->post['selected']) && $this->validateEnable()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $this->model_catalog_product->editProductStatus($product_id, 1);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $url = '';
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        $this->getList();
    }
    public function disable() {
        $this->load->language('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');
        if (isset($this->request->post['selected']) && $this->validateDisable()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $this->model_catalog_product->editProductStatus($product_id, 0);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $url = '';
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        $this->getList();
    }
	
	protected function validateEnable() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
	protected function validateDisable() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validateCopy() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function seoAutocomplete() {
		$json = array();
		if (!$this->user->hasPermission('access', 'catalog/product')) {
			$this->response->setStatusCode(403);
		} else {
			$filter_name = isset($this->request->get['filter_name']) ? trim((string)$this->request->get['filter_name']) : '';
			if ($filter_name !== '') {
				$this->load->model('catalog/product');
				foreach ($this->model_catalog_product->searchProductSeoUrls($filter_name, $this->config->get('config_limit_autocomplete')) as $row) {
					$json[] = array('keyword' => (string)$row['keyword']);
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->addHeader('Cache-Control: no-store');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])) {
			$this->load->model('catalog/product');
			$this->load->model('catalog/option');

			if (isset($this->request->get['filter_name'])) {
				$filter_name = $this->request->get['filter_name'];
			} else {
				$filter_name = '';
			}

			if (isset($this->request->get['filter_model'])) {
				$filter_model = $this->request->get['filter_model'];
			} else {
				$filter_model = '';
			}

			if (isset($this->request->get['limit'])) {
				$limit = (int)$this->request->get['limit'];
			} else {
				$limit = $this->config->get('config_limit_autocomplete');
			}

			$filter_data = array(
				'filter_name'  => $filter_name,
				'filter_model' => $filter_model,
				'start'        => 0,
				'limit'        => $limit
			);

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				$option_data = array();

				$product_options = $this->model_catalog_product->getProductOptions($result['product_id']);

				foreach ($product_options as $product_option) {
					$option_info = $this->model_catalog_option->getOption($product_option['option_id']);

					if ($option_info) {
						$product_option_value_data = array();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$option_value_info = $this->model_catalog_option->getOptionValue($product_option_value['option_value_id']);

							if ($option_value_info) {
								$product_option_value_data[] = array(
									'product_option_value_id' => $product_option_value['product_option_value_id'],
									'option_value_id'         => $product_option_value['option_value_id'],
									'name'                    => $option_value_info['name'],
									'price'                   => (float)$product_option_value['price'] ? $this->currency->format($product_option_value['price'], $this->config->get('config_currency')) : false,
									'price_prefix'            => $product_option_value['price_prefix']
								);
							}
						}

						$option_data[] = array(
							'product_option_id'    => $product_option['product_option_id'],
							'product_option_value' => $product_option_value_data,
							'option_id'            => $product_option['option_id'],
							'name'                 => $option_info['name'],
							'type'                 => $option_info['type'],
							'value'                => $product_option['value'],
							'required'             => $product_option['required']
						);
					}
				}

				$json[] = array(
					'product_id' => $result['product_id'],
					'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'model'      => $result['model'],
					'option'     => $option_data,
					'price'      => $result['price']
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    private function queueRelationRefresh($product_id) {
        $product_id = (int)$product_id;
        if ($product_id < 1 || !(bool)$this->config->get('codecart_relation_status')) { return; }
        try {
            $code = 'relation.generate.product.' . $product_id;
            $existing = $this->db->query("SELECT queue_id FROM `" . DB_PREFIX . "codecart_queue` WHERE code='" . $this->db->escape($code) . "' AND status IN ('pending','processing') LIMIT 1");
            if (!$existing->num_rows) {
                (new \CodeCart\Core\Queue($this->registry))->enqueue($code, 'cron/relation/batch', array('ids' => array($product_id)), 85, null, 3);
            }
        } catch (\Throwable $e) {
            if ($this->log) { $this->log->write('Auto Relation queue error for product #' . $product_id . ': ' . $e->getMessage()); }
        }
    }

}
