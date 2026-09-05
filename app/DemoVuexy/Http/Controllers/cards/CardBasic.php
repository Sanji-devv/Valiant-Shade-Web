<?php

namespace DemoVuexy\Http\Controllers\cards;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CardBasic extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.cards.cards-basic');
  }
}
