<?php
    public function select<{$NamePascal}>()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.name}>");
        $name = "<{$item.name}>";
        $attr = array(
            "id" => "<{$item.id}>",
            "class" => "<{$item.class}>",
        );
<{if $item.array_source}>
        $model = new <{$ModuleName}>_Model_<{$item.array_source.model}>();
        $select = $model->select()
            ->where("delete_flag = 0 or delete_flag is null")
<{if $item.array_source.where && $item.array_source.where|is_string}>
            ->where("<{$item.array_source.where}>")
<{elseif $item.array_source.where && $item.array_source.where|count > 1}>
<{foreach from=$item.array_source.where item=where}>
            ->where("<{$where}>")
<{/foreach}>
<{/if}>
<{if $item.array_source.sort}>
<{foreach from=$item.array_source.sort item=sort}>
            ->order("<{$sort}>")
<{/foreach}>
<{/if}>
            ;
        $vos = $model->fetchAll($select);
        $array = array();
<{if $item.array_source.default_option}>
<{foreach from=$item.array_source.default_option item=option}>
<{foreach from=$option item=opt}>
        $array["<{$opt.value}>"] = "<{$opt.label}>";
<{/foreach}>
<{/foreach}>
<{/if}>
        if($vos){
            foreach($vos as $vo){
                $array[$vo->get("<{$item.array_source.value}>")] = $vo->get("<{$item.array_source.label}>");
            }
        }
<{/if}>
        return $this->_zv->formSelect($name, $default, $attr, $array);
        
    }

