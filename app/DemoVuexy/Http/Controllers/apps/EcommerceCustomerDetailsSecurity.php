<?php

namespace DemoVuexy\Http\Controllers\apps;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EcommerceCustomerDetailsSecurity extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.apps.app-ecommerce-customer-details-security');
  }
}
