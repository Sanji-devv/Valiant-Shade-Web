<?php

namespace DemoVuexy\Http\Controllers\modal;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModalExample extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.modal.modal-examples');
  }
}
