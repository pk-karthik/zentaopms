<?php
/**
 * The control file of testsuite module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     testsuite
 * @version     $Id: control.php 5114 2013-07-12 06:02:59Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class testsuite extends control
{
    public $products = array();

    /**
     * Index page, header to browse.
     * 
     * @access public
     * @return void
     */
    public function index()
    {
        $this->locate($this->createLink('testsuite', 'browse'));
    }

    /**
     * Browse test suites. 
     * 
     * @param  int    $productID 
     * @param  string $orderBy 
     * @param  int    $recTotal 
     * @param  int    $recPerPage 
     * @param  int    $pageID 
     * @access public
     * @return void
     */
    public function browse($productID = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        /* Save session. */
        $this->session->set('testsuiteList', $this->app->getURI(true));

        /* Set menu. */
        $this->view->products = $this->products = $this->loadModel('product')->getPairs('nocode');
        $productID = $this->product->saveState($productID, $this->products);
        $this->testsuite->setMenu($this->products, $productID);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* Append id for secend sort. */
        $sort = $this->loadModel('common')->appendOrder($orderBy);

        $this->view->title       = $this->products[$productID] . $this->lang->colon . $this->lang->testsuite->common;
        $this->view->position[]  = html::a($this->createLink('testsuite', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[]  = $this->lang->testsuite->common;

        $this->view->productID   = $productID;
        $this->view->productName = $this->products[$productID];
        $this->view->orderBy     = $orderBy;
        $this->view->suites      = $this->testsuite->getSuites($productID, $sort, $pager);
        $this->view->users       = $this->loadModel('user')->getPairs('noclosed|noletter');
        $this->view->pager       = $pager;

        $this->display();
    }

    /**
     * Create a test suite.
     * 
     * @param  int    $productID 
     * @access public
     * @return void
     */
    public function create($productID)
    {
        if(!empty($_POST))
        {
            $suiteID = $this->testsuite->create($productID);
            if(dao::isError()) die(js::error(dao::getError()));
            $actionID = $this->loadModel('action')->create('testsuite', $suiteID, 'opened');
            die(js::locate($this->createLink('testsuite', 'browse', "productID=$productID"), 'parent'));
        }

        /* Set menu. */
        $this->view->products = $this->products = $this->loadModel('product')->getPairs('nocode');
        $productID  = $this->product->saveState($productID, $this->products);
        $this->testsuite->setMenu($this->products, $productID);

        $this->view->title      = $this->products[$productID] . $this->lang->colon . $this->lang->testsuite->create;
        $this->view->position[] = html::a($this->createLink('testsuite', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[] = $this->lang->testsuite->common;
        $this->view->position[] = $this->lang->testsuite->create;

        $this->view->productID    = $productID;
        $this->display();
    }

    /**
     * View a test suite.
     * 
     * @param  int    $suiteID 
     * @param  string $orderBy 
     * @param  int    $recTotal 
     * @param  int    $recPerPage 
     * @param  int    $pageID 
     * @access public
     * @return void
     */
    public function view($suiteID, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        /* Get test suite, and set menu. */
        $suite = $this->testsuite->getById($suiteID, true);
        if(!$suite) die(js::error($this->lang->notFound) . js::locate('back'));
        if($suite->type == 'private' and $suite->addedBy != $this->app->user->account and !$this->app->user->admin) die(js::error($this->lang->error->accessDenied) . js::locate('back'));
        $productID = $suite->product;

        $this->view->products = $this->products = $this->loadModel('product')->getPairs('nocode');
        $this->testsuite->setMenu($this->products, $productID);

        /* Save session. */
        $this->session->set('caseList', $this->app->getURI(true));

        /* Append id for secend sort. */
        $sort = $this->loadModel('common')->appendOrder($orderBy);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        $this->view->title      = "SUITE #$suite->id $suite->name/" . $this->products[$productID];
        $this->view->position[] = html::a($this->createLink('testsuite', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[] = $this->lang->testsuite->common;
        $this->view->position[] = $this->lang->testsuite->view;

        $this->view->productID = $productID;
        $this->view->suite     = $suite;
        $this->view->users     = $this->loadModel('user')->getPairs('noclosed|noletter');
        $this->view->actions   = $this->loadModel('action')->getList('testsuite', $suiteID);
        $this->view->cases     = $this->testsuite->getLinkedCases($suiteID, $sort, $pager);
        $this->view->orderBy   = $orderBy;
        $this->view->pager     = $pager;
        $this->view->branches  = $this->loadModel('branch')->getPairs($suite->product, 'noempty');

        $this->display();
    }

    /**
     * Edit a test suite.
     * 
     * @param  int    $suiteID 
     * @access public
     * @return void
     */
    public function edit($suiteID)
    {
        $suite = $this->testsuite->getById($suiteID);
        if(!empty($_POST))
        {
            $changes = $this->testsuite->update($suiteID);
            if(dao::isError()) die(js::error(dao::getError()));
            if($changes)
            {
                $objectType = $suite->type == 'library' ? 'caselib' : 'testsuite';
                $actionID = $this->loadModel('action')->create($objectType, $suiteID, 'edited');
                $this->action->logHistory($actionID, $changes);
            }
            $method = $suite->type == 'library' ? 'libView' : 'view';
            die(js::locate(inlink($method, "suiteID=$suiteID"), 'parent'));
        }

        if($suite->type == 'library')
        {
            /* Set lib menu. */
            $libraries = $this->testsuite->getLibraries();
            $suiteID   = $this->testsuite->saveLibState($suiteID, $libraries);
            $this->testsuite->setLibMenu($libraries, $suiteID);

            $this->view->title      = $libraries[$suiteID] . $this->lang->colon . $this->lang->testsuite->edit;
            $this->view->position[] = html::a($this->createLink('testsuite', 'library', "libID=$suiteID"), $libraries[$suiteID]);
            $this->view->position[] = $this->lang->caselib->common;
        }
        else
        {
            if($suite->type == 'private' and $suite->addedBy != $this->app->user->account and !$this->app->user->admin) die(js::error($this->lang->error->accessDenied) . js::locate('back'));

            /* Get suite info. */
            $this->view->products = $this->products = $this->loadModel('product')->getPairs('nocode');
            $productID = $this->product->saveState($suite->product, $this->products);

            /* Set menu. */
            $this->testsuite->setMenu($this->products, $productID);

            $this->view->title      = $this->products[$productID] . $this->lang->colon . $this->lang->testsuite->edit;
            $this->view->position[] = html::a($this->createLink('testsuite', 'browse', "productID=$productID"), $this->products[$productID]);
            $this->view->position[] = $this->lang->testsuite->common;
        }

        $this->view->position[] = $this->lang->testsuite->edit;

        $this->view->suite = $suite;
        $this->display();
    }

    /**
     * Delete a test suite.
     * 
     * @param  int    $suiteID 
     * @param  string $confirm yes|no
     * @access public
     * @return void
     */
    public function delete($suiteID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            die(js::confirm($this->lang->testsuite->confirmDelete, inlink('delete', "suiteID=$suiteID&confirm=yes")));
        }
        else
        {
            if($suite->type == 'private' and $suite->addedBy != $this->app->user->account and !$this->app->user->admin) die(js::error($this->lang->error->accessDenied) . js::locate('back'));

            $this->testsuite->delete($suiteID);

            /* if ajax request, send result. */
            if($this->server->ajax)
            {
                if(dao::isError())
                {
                    $response['result']  = 'fail';
                    $response['message'] = dao::getError();
                }
                else
                {
                    $response['result']  = 'success';
                    $response['message'] = '';
                }
                $this->send($response);
            }
            die(js::reload('parent'));
        }
    }

    /**
     * Link cases to a test suite.
     * 
     * @param  int    $suiteID 
     * @param  int    $param 
     * @param  int    $recTotal 
     * @param  int    $recPerPage 
     * @param  int    $pageID 
     * @access public
     * @return void
     */
    public function linkCase($suiteID, $param = 0, $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        if(!empty($_POST))
        {
            $this->testsuite->linkCase($suiteID);
            $this->locate(inlink('view', "suiteID=$suiteID"));
        }

        /* Save session. */
        $this->session->set('caseList', $this->app->getURI(true));

        /* Get suite and product id. */
        $this->view->products = $this->products = $this->loadModel('product')->getPairs('nocode');
        $suite      = $this->testsuite->getById($suiteID);
        $productID = $this->product->saveState($suite->product, $this->products);

        /* Save session. */
        $this->testsuite->setMenu($this->products, $productID);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* Build the search form. */
        $this->loadModel('testcase');
        $this->config->testcase->search['params']['product']['values']= array('' => '', $productID => $this->products[$productID], 'all' => $this->lang->testcase->allProduct);
        $this->config->testcase->search['params']['module']['values'] = $this->loadModel('tree')->getOptionMenu($productID, $viewType = 'case');
        $this->config->testcase->search['module']    = 'testsuite';
        $this->config->testcase->search['actionURL'] = inlink('linkCase', "suiteID=$suiteID&param=myQueryID");
        if($this->session->currentProductType == 'normal')
        {
            unset($this->config->testcase->search['fields']['branch']);
            unset($this->config->testcase->search['params']['branch']);
        }
        else
        {
            $this->config->testcase->search['fields']['branch'] = $this->lang->product->branch;
            $branches = array('' => '') + $this->loadModel('branch')->getPairs($suite->product, 'noempty');
            $this->config->testcase->search['params']['branch']['values'] = $branches;
        }
        if(!$this->config->testcase->needReview) unset($this->config->testcase->search['params']['status']['values']['wait']);
        $this->loadModel('search')->setSearchParams($this->config->testcase->search);

        $this->view->title      = $suite->name . $this->lang->colon . $this->lang->testsuite->linkCase;
        $this->view->position[] = html::a($this->createLink('testsuite', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[] = $this->lang->testsuite->common;
        $this->view->position[] = $this->lang->testsuite->linkCase;

        $this->view->users   = $this->loadModel('user')->getPairs('noletter');
        $this->view->cases   = $this->testsuite->getUnlinkedCases($suite, $param, $pager);
        $this->view->suiteID = $suiteID;
        $this->view->pager   = $pager;
        $this->view->suite   = $suite;

        $this->display();
    }

    /**
     * Remove a case from test suite.
     * 
     * @param  int    $suiteID 
     * @param  int    $rowID 
     * @param  string $confirm 
     * @access public
     * @return void
     */
    public function unlinkCase($suiteID, $rowID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            die(js::confirm($this->lang->testsuite->confirmUnlinkCase, $this->createLink('testsuite', 'unlinkCase', "rowID=$rowID&confirm=yes")));
        }
        else
        {
            $response['result']  = 'success';
            $response['message'] = '';

            $this->dao->delete()->from(TABLE_SUITECASE)->where('`case`')->eq((int)$rowID)->andWhere('suite')->eq($suiteID)->exec();
            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
            }
            $this->send($response);
        }
    }

    /**
     * Batch unlink cases.
     *
     * @param  int    $suiteID
     * @access public
     * @return void
     */
    public function batchUnlinkCases($suiteID)
    {
        if(isset($_POST['caseIDList']))
        {
            $this->dao->delete()->from(TABLE_SUITECASE)
                ->where('suite')->eq((int)$suiteID)
                ->andWhere('`case`')->in($this->post->caseIDList)
                ->exec();
        }

        die(js::locate($this->createLink('testsuite', 'view', "suiteID=$suiteID")));
    }

    /**
     * Show library case.
     * 
     * @param  int    $libID 
     * @param  string $browseType 
     * @param  int    $param 
     * @param  string $orderBy 
     * @param  int    $recTotal 
     * @param  int    $recPerPage 
     * @param  int    $pageID 
     * @access public
     * @return void
     */
    public function library($libID = 0, $browseType = 'all', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        /* Set browse type. */
        $browseType = strtolower($browseType);

        $libraries = $this->testsuite->getLibraries();
        if(empty($libraries)) $this->locate(inlink('createLib'));

        /* Save session. */
        $this->session->set('caseList', $this->app->getURI(true));

        /* Set menu. */
        $libID = $this->testsuite->saveLibState($libID, $libraries);
        setcookie('preCaseLibID', $libID, $this->config->cookieLife, $this->config->webRoot);
        if($this->cookie->preCaseLibID != $libID)
        {
            $_COOKIE['libCaseModule'] = 0;
            setcookie('libCaseModule', 0, $this->config->cookieLife, $this->config->webRoot);
        }

        if($browseType == 'bymodule') setcookie('libCaseModule', (int)$param, $this->config->cookieLife, $this->config->webRoot);
        if($browseType != 'bymodule') $this->session->set('libBrowseType', $browseType);
        $moduleID = ($browseType == 'bymodule') ? (int)$param : ($browseType == 'bysearch' ? 0 : ($this->cookie->libCaseModule ? $this->cookie->libCaseModule : 0));
        $queryID  = ($browseType == 'bysearch') ? (int)$param : 0;

        /* Set lib menu. */
        $this->testsuite->setLibMenu($libraries, $libID);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* Build the search form. */
        $this->loadModel('testcase');
        $actionURL = $this->createLink('testsuite', 'library', "libID=$libID&browseType=bySearch&queryID=myQueryID");
        $this->testsuite->buildSearchForm($libID, $libraries, $queryID, $actionURL);

        /* Append id for secend sort. */
        $sort = $this->loadModel('common')->appendOrder($orderBy);

        /* save session .*/
        $cases = $this->testsuite->getLibCases($libID, $browseType, $queryID, $moduleID, $sort, $pager);
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'testcase', true);

        $this->loadModel('datatable');
        $this->loadModel('tree');
        $showModule = !empty($this->config->datatable->testsuiteLibrary->showModule) ? $this->config->datatable->testsuiteLibrary->showModule : '';
        $this->view->modulePairs = $showModule ? $this->tree->getModulePairs($libID, 'caselib', $showModule) : array();

        $this->view->title      = $this->lang->caselib->common . $this->lang->colon . $libraries[$libID];
        $this->view->position[] = html::a($this->createLink('testsuite', 'library', "libID=$libID"), $libraries[$libID]);

        $this->view->libID         = $libID;
        $this->view->libName       = $libraries[$libID];
        $this->view->cases         = $cases;
        $this->view->orderBy       = $orderBy;
        $this->view->users         = $this->loadModel('user')->getPairs('noclosed|noletter');
        $this->view->modules       = $this->tree->getOptionMenu($libID, $viewType = 'caselib', $startModuleID = 0);
        $this->view->moduleTree    = $this->tree->getTreeMenu($libID, $viewType = 'caselib', $startModuleID = 0, array('treeModel', 'createCaseLibLink'), '');
        $this->view->pager         = $pager;
        $this->view->browseType    = $browseType;
        $this->view->moduleID      = $moduleID;
        $this->view->moduleName    = $moduleID ? $this->tree->getById($moduleID)->name : $this->lang->tree->all;
        $this->view->param         = $param;
        $this->view->setShowModule = true;

        $this->display();
    }

    /**
     * Create lib 
     * 
     * @access public
     * @return void
     */
    public function createLib()
    {
        if(!empty($_POST))
        {
            $libID = $this->testsuite->createLib();
            if(dao::isError()) die(js::error(dao::getError()));
            $this->loadModel('action')->create('caselib', $libID, 'opened');
            die(js::locate($this->createLink('testsuite', 'library', "libID=$libID"), 'parent'));
        }

        /* Set menu. */
        $libraries = $this->testsuite->getLibraries();
        $libID     = $this->testsuite->saveLibState(0, $libraries);
        $this->testsuite->setLibMenu($libraries, $libID);

        $this->view->title      = $this->lang->caselib->common . $this->lang->colon . $this->lang->testsuite->createLib;
        $this->view->position[] = $this->lang->caselib->common;
        $this->view->position[] = $this->lang->testsuite->createLib;
        $this->display();
    }

    /**
     * Create case for library.
     * 
     * @param  int    $libID 
     * @param  int    $moduleID 
     * @access public
     * @return void
     */
    public function createCase($libID, $moduleID = 0, $param = 0)
    {
        if(!empty($_POST))
        {
            $caseResult = $this->loadModel('testcase')->create($bugID = 0);
            if(!$caseResult or dao::isError()) die(js::error(dao::getError()));

            $caseID = $caseResult['id'];
            if($caseResult['status'] == 'exists')
            {
                echo js::alert(sprintf($this->lang->duplicate, $this->lang->testcase->common));
                die(js::locate($this->createLink('testcase', 'view', "caseID=$caseID"), 'parent'));
            }

            $this->loadModel('action')->create('case', $caseID, 'Opened');

            /* If link from no head then reload. */
            if(isonlybody()) die(js::reload('parent'));
            die(js::locate($this->createLink('testsuite', 'library', "libID={$libID}&browseType=byModule&param={$_POST['module']}"), 'parent'));
        }
        /* Set lib menu. */
        $libraries = $this->testsuite->getLibraries();
        $libID     = $this->testsuite->saveLibState($libID, $libraries);
        $this->testsuite->setLibMenu($libraries, $libID);

        $type         = 'feature';
        $stage        = '';
        $pri          = 3;
        $caseTitle    = '';
        $precondition = '';
        $keywords     = '';
        $steps        = array();

        $this->loadModel('testcase');
        if($param)
        {
            $testcase     = $this->testcase->getById((int)$param);
            $type         = $testcase->type ? $testcase->type : 'feature';
            $stage        = $testcase->stage;
            $pri          = $testcase->pri;
            $storyID      = $testcase->story;
            $caseTitle    = $testcase->title;
            $precondition = $testcase->precondition;
            $keywords     = $testcase->keywords;
            $steps        = $testcase->steps;
        }

        if(count($steps) < $this->config->testcase->defaultSteps)
        {
            $paddingCount = $this->config->testcase->defaultSteps - count($steps);
            $step = new stdclass();
            $step->type   = 'item';
            $step->desc   = '';
            $step->expect = '';
            for($i = 1; $i <= $paddingCount; $i ++) $steps[] = $step;
        }

        $this->view->title      = $libraries[$libID] . $this->lang->colon . $this->lang->testcase->create;
        $this->view->position[] = html::a($this->createLink('testsuite', 'library', "libID=$libID"), $libraries[$libID]);
        $this->view->position[] = $this->lang->testsuite->common;
        $this->view->position[] = $this->lang->testcase->create;

        $this->view->libraries        = $libraries;
        $this->view->libID            = $libID;
        $this->view->currentModuleID  = (int)$moduleID;
        $this->view->caseTitle        = $caseTitle;
        $this->view->type             = $type;
        $this->view->stage            = $stage;
        $this->view->pri              = $pri;
        $this->view->precondition     = $precondition;
        $this->view->keywords         = $keywords;
        $this->view->steps            = $steps;
        $this->view->moduleOptionMenu = $this->loadModel('tree')->getOptionMenu($libID, $viewType = 'caselib', $startModuleID = 0);
        $this->display();
    }

    /**
     * View library
     * 
     * @param  int    $libID 
     * @access public
     * @return void
     */
    public function libView($libID)
    {
        $lib = $this->testsuite->getById($libID);

        /* Set lib menu. */
        $libraries = $this->testsuite->getLibraries();
        $this->testsuite->setLibMenu($libraries, $libID);

        $this->loadModel('testcase');
        $this->view->title      = $lib->name . $this->lang->colon . $this->lang->testsuite->view;
        $this->view->position[] = html::a($this->createLink('testsuite', 'library', "libID=$libID"), $lib->name);
        $this->view->position[] = $this->lang->testsuite->common;
        $this->view->position[] = $this->lang->testsuite->view;

        $this->view->lib      = $lib;
        $this->view->actions  = $this->loadModel('action')->getList('caselib', $libID);
        $this->display();
    }

    /**
     * Ajax get drop menu.
     * 
     * @param  int    $libID 
     * @param  string $module 
     * @param  string $method 
     * @param  string $extra 
     * @access public
     * @return void
     */
    public function ajaxGetDropMenu($libID, $module, $method, $extra)
    {
        $this->view->link      = $this->testsuite->getLibLink($module, $method, $extra);
        $this->view->libID     = $libID;
        $this->view->module    = $module;
        $this->view->method    = $method;
        $this->view->extra     = $extra;

        $libraries = $this->testsuite->getLibraries();

        $this->view->libraries       = $libraries;
        $this->view->librariesPinyin = common::convert2Pinyin($libraries);
        $this->display();
    }

    /**
     * The results page of search.
     * 
     * @param  string  $keywords 
     * @param  string  $module 
     * @param  string  $method 
     * @param  mix     $extra 
     * @access public
     * @return void
     */
    public function ajaxGetMatchedItems($keywords, $module, $method, $extra)
    {
        $libraries = $this->dao->select('*')->from(TABLE_TESTSUITE)->where('deleted')->eq(0)->andWhere('name')->like("%$keywords%")->andWhere('type')->eq('library')->orderBy('`id` desc')->fetchAll();
        $this->view->link      = $this->testsuite->getLibLink($module, $method, $extra);
        $this->view->libraries = $libraries;
        $this->view->keywords  = $keywords;
        $this->display();
    }
}
