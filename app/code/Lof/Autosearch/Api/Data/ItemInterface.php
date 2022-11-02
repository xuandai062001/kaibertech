<?php
/**
 * Lof Autosearch is a powerful tool for managing the processing return and exchange requests within your workflow. This, in turn, allows your customers to request and manage returns and exchanges directly from your webstore. The Extension compatible with magento 2.x
 * Copyright (C) 2017  Landofcoder.com
 * 
 * This file is part of Lof/Autosearch.
 * 
 * Lof/Autosearch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Lof\Autosearch\Api\Data;

interface ItemInterface
{

    /**
     * Get products
     * @return mixed|null
     */
    public function getProducts();

    /**
     * Set products
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setProducts($products, $queryText);

    /**
     * Get blog posts
     * @return mixed|null
     */
    public function getBlogPosts();

    /**
     * Set blog posts
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setBlogPosts($posts, $queryText);

    /**
     * Get brands
     * @return mixed|null
     */
    public function getBrands();

    /**
     * Set brand
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setBrand($brands, $queryText);

    /**
     * Get cms page
     * @return mixed|null
     */
    public function getCmsPage();

    /**
     * Set cms_page
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setCmsPage($cms_page, $queryText);

     /**
     * Get faq
     * @return mixed|null
     */
    public function getFaq();

    /**
     * Set faq
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setFaq($faq_questions, $queryText);

    /**
     * Get suggested
     * @return mixed|null
     */
    public function getSuggested();

    /**
     * Set suggested
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setSuggested($suggested, $queryText);

    /**
     * Get product total
     * @return int
     */
    public function getTotal();

    /**
     * Set suggested
     * @param int $product_total
     * @return $this
     */
    public function setTotal($product_total);

    /**
     * Get product total
     * @return int
     */
    public function getCmsTotal();

    /**
     * Set suggested
     * @param int $cms_total
     * @return $this
     */
    public function setCmsTotal($cms_total);

     /**
     * Get product total
     * @return int
     */
    public function getBlogTotal();

    /**
     * Set suggested
     * @param int $vesblog_total
     * @return $this
     */
    public function setBlogTotal($vesblog_total);

    /**
     * Get product total
     * @return int
     */
    public function getBrandTotal();

    /**
     * Set vesbrand_total
     * @param int $vesbrand_total
     * @return $this
     */
    public function setBrandTotal($vesbrand_total);

     /**
     * Get Faq total
     * @return int
     */
    public function getFaqTotal();

    /**
     * Set faq
     * @param int $faq_total
     * @return $this
     */
    public function setFaqTotal($faq_total);

    /**
     * Get Catalog Category total
     * @return int
     */
    public function getCategoryTotal();

    /**
     * Set catalog category total
     * @param int $category_total
     * @return $this
     */
    public function setCategoryTotal($category_total);

    /**
     * Get categories
     * @return mixed|null
     */
    public function getCategory();

    /**
     * Set categories
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setCategory($categories, $queryText);
}
