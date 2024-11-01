<?php
if(isset($_GET["update"]) && $_GET["update"]==1){
    $values=$_POST["values"];
    $post_id=$values['id'];
    $status=$values['status'];
    $slug=$values['slug'];
    $title=$values['title'];
    $author=$values['usersID'];
    $cats=$values['category'];
    $tags=$values['postTags'];
    global $wpdb;
    $wpdb->update( 
            $wpdb->posts, 
            array( 
                'post_author' => $author,  
                'post_status' => $status,
                'post_name'   => $slug,
                'post_title'  => stripslashes($title),
              
            ), 
            array('ID'=>$post_id),
            array( 
                '%d',
                '%s',
                '%s',         
                '%s'
            ) 
        );  
    if($cats!=""){
        foreach($cats as $cat){
            $c=get_term_by('name',$cat,'category');
            wp_set_object_terms($post_id,$c->name,'category',false);
        }
    }
    else{
        wp_set_object_terms($post_id,1,'category',false);
    }
    if($tags!=""){
        foreach($tags as $tag){
            $c=get_term_by('name',$tag,'post_tag');
            wp_set_object_terms($post_id,$c->name,'post_tag',true);
        }
    }
    else{
        wp_delete_object_term_relationships( $post_id, 'post_tag' );
    }
    exit;
}
?>
<div id="grid"></div>
<span id="centeredNotification"></span>
<div class="k-content">
    
<script>
    function updateAjax(Json) {
        jQuery.ajax({
            type: "POST",
            async:false,
            url: "<?php echo $_SERVER["REQUEST_URI"]?>&update=1",
            data: { values: Json },
            success: function () {
                updatedSuccess.show("<?php _e('Post updated succesfully','manyposts')?>", "success");
            }
        })
    }
    function onShow(e) {
        if (!jQuery("." + e.sender._guid)[1]) {
            var element = e.element.parent(),
                eWidth = element.width(),
                eHeight = element.height(),
                wWidth = jQuery(window).width(),
                wHeight = jQuery(window).height(),
                newTop, newLeft;

            newLeft = Math.floor(wWidth / 2 - eWidth / 2);
            newTop = Math.floor(wHeight / 2 - eHeight / 2);

            e.element.parent().css({ top: newTop, left: newLeft, zIndex: 10004 });
        }
    }
    var categoryWarning = jQuery("#centeredNotification").kendoNotification({
        stacking: "down",
        show: onShow,
        button: true,
        autoHideAfter: 0,
        width: 450
    }).data("kendoNotification");
    var updatedSuccess = jQuery("#centeredNotification").kendoNotification({
        stacking: "down",
        show: onShow,
        button: true,
        autoHideAfter: 2000,
        width: 240
    }).data("kendoNotification");

    jQuery(document).ready(function () {
        <?php
//POST TAGS                     
        $tags=get_tags();
        $_jst="var _tags = [";
        foreach($tags as $tag)
        {
            $_jst.='"' .$tag->name . '",';

    }
	if($_jst !="var _tags = [")
		$_jst=substr($_jst,0,strlen($_jst)-1);
    $_jst.="];";
    $_jst.="\n";
    echo $_jst;
//POST CATEGORIES                   
    $terms=get_terms('category');
    $open="var categories = [";
    $_js=$open;
    foreach($terms as $term)
    {
        $parent = get_term($term->parent, 'category'); // get parent term

    $children = get_term_children($term->term_id, 'category'); // get children

        if($parent->term_id!="" && sizeof($children)>0) {
            
            $subs=get_term_children($term->term_id, 'category');
            foreach($subs as $sub)
                $sub=get_term_by('id',$sub,'category');
            $_js.='"' .$sub->name . '",';


        }
        elseif(($parent->term_id!="") && (sizeof($children)==0)) {

            $_js.='"' .$term->name . '",';

        }
        elseif(($parent->term_id=="") && (sizeof($children)>0)) {

            // no parent, has child

        }

    else{
                $_js.='"' .$term->name . '",';
    }

    }
    if(strlen($_js)>strlen($open))
        $_js=substr($_js,0,strlen($_js)-1);
    $_js.="];";
    $_js.="\n";
    echo $_js;
//POST AUTHOR
    $users=get_users();
    $_jsu="var users = [";
    foreach ( $users as $user ) {
        $_jsu.='{"value":"' .$user->ID  . '" , "text": "' .$user->display_name . '"},';
    }
    $_jsu=substr($_jsu,0,strlen($_jsu)-1);
    $_jsu.="];"; 
    $_jsu.="\n";
    echo $_jsu;
    
//POSTS
    global $wpdb;
    $options = get_option( 'manyposts_settings' );
    $sql="SELECT * FROM ".$wpdb->posts."  WHERE  post_type LIKE 'post'  AND (post_status LIKE 'publish'";
    if($options['include_drafts']==1)
        $sql.=" OR post_status LIKE 'draft' OR post_status LIKE 'pending'";
    $sql.=") LIMIT 2500";

    $result=$wpdb->get_results($sql);
    $l=0;
    foreach ( $result as $post )
	{
		setup_postdata( $post );
        $array_articoli[$l] = new StdClass;
        $array_articoli[$l]->id=$post->ID;
        $array_articoli[$l]->title=$post->post_title;
        $array_articoli[$l]->slug=$post->post_name;
        $array_articoli[$l]->status=$post->post_status;
        
        $tags=wp_get_post_tags($post->ID);
        
        foreach($tags as $tag)
        {
            $array_articoli[$l]->postTags[]=$tag->name;
        }
        if(count($array_articoli[$l]->postTags)==0)
            $array_articoli[$l]->postTags[]="";
        $cats=get_the_terms($post->ID,'category');
        if($cats){
            foreach($cats as $cat)
            {
                $array_articoli[$l]->category[]=$cat->name;
            }
        }
        if(count($array_articoli[$l]->category)==0)
            $array_articoli[$l]->category[]=get_term_by('id',1,'category')->name;
        $array_articoli[$l]->usersID=get_the_author_meta('ID',$post->post_author);
        $l++;
    }

    $postsJson=json_encode($array_articoli,64);
    echo "var _posts={\"data\":".$postsJson."};";
    ?>
    var _status = ["publish", "draft", "pending", "trash"];
    function catEditor(container, options) {
        jQuery("<select multiple='multiple' " +
            "data-bind='value : category'/>").appendTo(container).kendoMultiSelect({
                dataSource: categories,
                change: function (e) {
                    var value = this.value();
                    if (!value.length)
                        categoryWarning.show("<?php _e('Warning: empty category is not allowed, the post will be assigned to the default uncategorized taxonomy','manyposts')?>", "warning");
                }
            });
    }
    function tagEditor(container, options) {
        jQuery("<select multiple='multiple' " +
            "data-bind='value : postTags'/>").appendTo(container).kendoMultiSelect({
                dataSource: _tags
            });
    }
    function statusEditor(container, options) {
        jQuery("<select data-bind='value : status'/>").appendTo(container).kendoDropDownList({
            dataSource: _status
        });
    }

    jQuery("#grid").kendoGrid({
        editable: "popup", height: 450, width: 600,
        dataSource: {
            transport:
                {
                    read: function (e) {
                        e.success(_posts);
                    },
                    update: function (e) {  
                        e.success(updateAjax(e.data));
                    },
                },
            schema:
                    {
                        total: function (response) {
                            return jQuery(response.data).length;
                        },
                        data: "data",
                        model: {
                            id: "id",
                            fields: {
                                title: { editable: true, nullable: false },
                                slug: { editable: true, nullable: false },
                                status: { field: "status", editable: true },
                                usersID: { field: "usersID", type: "number", nullable: false },
                                category: { editable: true },
                                postTags: { editable: true }
                            }
                        }
                    },
            pageSize: 50
        },
        sortable: true,
        serverPaging: true,
        pageable:
            {
                pageSizes: [20, 50, 100],
                messages:
                    {
                        display: "<?php _e('Showing','manyposts') ?> {0}-{1}  <?php _e('of','manyposts') ?> {2} <?php _e('total','manyposts') ?>",
                        of: "<?php _e('of','manyposts') ?> {0}",
                        itemsPerPage: "<?php _e('Posts per page','manyposts') ?>",
                        first: "<?php _e('First page','manyposts') ?>",
                        last: "<?php _e('Last page','manyposts') ?>",
                        next: "<?php _e('Next','manyposts') ?>",
                        previous: "<?php _e('Prev.','manyposts') ?>",
                        refresh: "<?php _e('Reload','manyposts') ?>",
                        morePages: "<?php _e('More','manyposts') ?>"
                    },
            },
        filterable:
            {
                messages:
                    {
                        info: "<?php _e('Filter by','manyposts') ?> "
                    },
                extra: false,
                operators:
                    {
                        string:
                            {
                                contains: "<?php _e('Contains','manyposts') ?> ",
                                startswith: "<?php _e('Starts with','manyposts') ?>",
                                eq: "<?php _e('Equal','manyposts') ?>",
                                neq: "<?php _e('Not equal','manyposts') ?>"
                            }
                    }
            },
        columns: [
            {
                title: "<?php _e('Title','manyposts') ?>",
                field: "title",
                width: 220,
                groupable: false
            },
            {
                title: "<?php _e('Slug','manyposts') ?>",
                field: "slug",
                width: 180,
                groupable: false
            },
            {
                title: "<?php _e('Status','manyposts') ?>",
                field: "status",
                editor: statusEditor,
                //template: "#= status #",
                width: 80,
                groupable: true
            },
            {
                field: "usersID",  values: users,
                title: "<?php _e('Author','manyposts') ?>",
                width: 120,
                groupable: true
            },
            {
                title: "<?php _e('Categories','manyposts') ?>",
                field: "category",
                width: 200,
                editor: catEditor,
                template: "#= category.join(', ') #",
                groupable: false
            },
            {
                title: "<?php _e('Tags','manyposts') ?>",
                field: "postTags",
                width: 200,
                editor: tagEditor,
                template: "#= postTags.join(', ') #",
                groupable: false
            },
            {
                command: ["edit", {
                    name: "<?php _e('Open','manyposts') ?>",
					    
                    click: function (e) {
                        var tr = jQuery(e.target).closest("tr"); // get the current table row (tr)
                        var data = this.dataItem(tr);
                        window.open("<?php echo home_url() ?>/wp-admin/post.php?post=" + data.id + "&action=edit");
                    }
                }], title: "&nbsp;", width: "150px"
            }
        ],
        height: 600,
    }).data("kendoGrid");

    setTimeout(function () {
        jQuery('body').addClass('folded');
    }, 300)
    });


</script>
</div>
<div class="manyposts_credits">
    [Wp Many Posts is a plugin by <a href="http://softrade.it/wp-many-posts-wordpress-plugin" target="_blank">SOFTRADE</a>, a WP / C# / jQuery coding italian company.]
</div>
<style>
#centeredNotification{display:none;width:300px!important}
.reference td{font-size:0.6em; word-wrap:normal; width:10%}
.reference th{ text-align:left}         
div.k-edit-form-container
{
    width: 600px!important;

}
    .k-edit-field, .k-edit-form-container .editor-field {

        width: 70%!important;
    }
.k-edit-label, .k-edit-form-container .editor-label {
    text-align: left;
    width:20%
}
.k-window .k-textbox{width:100%!important}
.k-grid .k-grouping-header {color:rgba(255, 255, 255, 0.8)!important;}
.manyposts_credits{font-style:italic;padding:20px;}
</style>