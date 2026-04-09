<?php

namespace App\Helpers;

use App\Models\Category;

class GetAllCategoryIdsHelper
{
    public static function getAllCategoryIds($categoryId)
    {
        $ids = [$categoryId];

        $children = Category::where('parent_id', $categoryId)->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, self::getAllCategoryIds($child->id));
        }

        return $ids;
    }
}