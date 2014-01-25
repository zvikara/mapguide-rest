<?php

require_once "controller.php";

class MgSiteAdminController extends MgBaseController {
    public function __construct($app) {
        parent::__construct($app);
    }

    public function GetSiteStatus($format) {
        //Check for unsupported representations
        $fmt = $this->ValidateRepresentation($format, array("xml", "json"));

        $this->EnsureAuthenticationForSite();
        $admin = new MgServerAdmin();
        $admin->Open($this->userInfo);
        $status = $admin->GetSiteStatus();

        $mimeType = MgMimeType::Xml;
        if ($fmt === "json") {
            $mimeType = MgMimeType::Json;
        }
        $this->OutputMgPropertyCollection($status, $mimeType);
    }

    public function GetSiteVersion() {
        $this->EnsureAuthenticationForSite();
        $admin = new MgServerAdmin();
        $admin->Open($this->userInfo);
        $this->app->response->setBody($admin->GetSiteVersion());
    }

    public function EnumerateGroups($format) {
        //Check for unsupported representations
        $fmt = $this->ValidateRepresentation($format, array("xml", "json"));

        $that = $this;
        $this->EnsureAuthenticationForHttp(function($req, $param) use ($that, $fmt) {
            $param->AddParameter("OPERATION", "ENUMERATEGROUPS");
            $param->AddParameter("VERSION", "1.0.0");
            if ($fmt === "json")
                $param->AddParameter("FORMAT", MgMimeType::Json);
            else
                $param->AddParameter("FORMAT", MgMimeType::Xml);
            if ($fmt === "json") {
                //Instructs ExecuteHttpRequest to force convert the XML content to JSON. This is a workaround
                //for mapagent APIs that do not support the FORMAT request parameter to let us specify a JSON
                //response (which is a bug, because they should!)
                $param->AddParameter("X-FORCE-JSON-CONVERSION", "true");
            }
            $that->ExecuteHttpRequest($req);
        });
    }

    public function EnumerateUsersForGroup($groupName, $format) {
        //Check for unsupported representations
        $fmt = $this->ValidateRepresentation($format, array("xml", "json"));

        $that = $this;
        $this->EnsureAuthenticationForHttp(function($req, $param) use ($that, $fmt, $groupName) {
            $param->AddParameter("OPERATION", "ENUMERATEUSERS");
            $param->AddParameter("GROUP", $groupName);
            $param->AddParameter("VERSION", "1.0.0");
            if ($fmt === "json")
                $param->AddParameter("FORMAT", MgMimeType::Json);
            else
                $param->AddParameter("FORMAT", MgMimeType::Xml);
            $that->ExecuteHttpRequest($req);
        });
    }

    public function EnumerateGroupsForUser($userName, $format) {
        //Check for unsupported representations
        $fmt = $this->ValidateRepresentation($format, array("xml", "json"));

        $this->EnsureAuthenticationForSite();
        $siteConn = new MgSiteConnection();
        $siteConn->Open($this->userInfo);

        $site = $siteConn->GetSite();
        try {
            $user = $site->GetUserForSession();
        } catch (MgException $ex) {
            //Could happen if we have non-anonymous credentials in the http authentication header
        }
        $content = $site->EnumerateGroups($userName);

        if ($fmt === "json") {
            $this->OutputXmlByteReaderAsJson($content);
        } else {
            $this->app->response->header("Content-Type", $content->GetMimeType());
            $this->OutputByteReader($content);
        }
    }

    public function EnumerateRolesForUser($userName, $format) {
        //Check for unsupported representations
        $fmt = $this->ValidateRepresentation($format, array("xml", "json"));

        $this->EnsureAuthenticationForSite();
        $siteConn = new MgSiteConnection();
        $siteConn->Open($this->userInfo);

        $site = $siteConn->GetSite();
        try {
            $user = $site->GetUserForSession();
        } catch (MgException $ex) {
            //Could happen if we have non-anonymous credentials in the http authentication header
        }

        $content = $site->EnumerateRoles($userName);
        $mimeType = MgMimeType::Xml;
        if ($fmt === "json") {
            $mimeType = MgMimeType::Json;
        } 
        $this->app->response->header("Content-Type", $mimeType);
        $this->OutputMgStringCollection($content, $mimeType);
    }
}

?>