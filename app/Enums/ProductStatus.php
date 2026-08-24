<?php

namespace App\Enums;

enum ProductStatus: string
{
  case Available = 'available';
  case OutOfStock = 'out_of_stock';
  case AwaitingRestock = 'awaiting_restock';
  case Archived = 'archived';
}
