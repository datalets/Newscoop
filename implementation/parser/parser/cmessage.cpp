/******************************************************************************

CAMPSITE is a Unicode-enabled multilingual web content
management system for news publications.
CAMPFIRE is a Unicode-enabled java-based near WYSIWYG text editor.
Copyright (C)2000,2001  Media Development Loan Fund
contact: contact@campware.org - http://www.campware.org
Campware encourages further development. Please let us know.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

******************************************************************************/


#include <libxml/xmlreader.h>

#include "cmessage.h"
#include "globals.h"


CBinaryStringValue::CBinaryStringValue(long p_nSize, const char* p_pchValue)
	: m_nSize(p_nSize)
{
	if (p_nSize == 0 || p_pchValue == 0)
	{
		m_pchValue = 0;
		return;
	}
	m_pchValue = new char[m_nSize];
	memcpy(m_pchValue, p_pchValue, m_nSize);
}

CBinaryStringValue::CBinaryStringValue(const CBinaryStringValue& p_rcoSource)
	: m_nSize(p_rcoSource.m_nSize)
{
	m_pchValue = new char[m_nSize];
	memcpy(m_pchValue, p_rcoSource.m_pchValue, m_nSize);
}

const CBinaryStringValue& CBinaryStringValue::operator = (const CBinaryStringValue& p_rcoSource)
{
	if (m_pchValue)
		delete m_pchValue;
	m_nSize = p_rcoSource.m_nSize;
	m_pchValue = new char[m_nSize];
	memcpy(m_pchValue, p_rcoSource.m_pchValue, m_nSize);
	return *this;
}


CParameterMap::~CParameterMap()
{
	String2Value::iterator coIt = m_coParameters.begin();
	for (; coIt != m_coParameters.end(); coIt = m_coParameters.begin())
	{
		CValue* pcoVal = (*coIt).second;
		(*coIt).second = NULL;
		m_coParameters.erase(coIt);
		delete pcoVal;
	}
}

const CValue* CParameterMap::valueOf(const string& p_rcoParameter) const throw(out_of_range)
{
	String2Value::const_iterator coIt = m_coParameters.find(p_rcoParameter);
	if (coIt == m_coParameters.end())
		throw out_of_range(string("invalid attribute name ") + p_rcoParameter);
	return (*coIt).second;
}

// general message elements
const char* CampsiteMessage = "CampsiteMessage";
const char* MessageType = "MessageType";
const char* MessageId = "MessageId";


// CMsgURLRequest static members initialisation
const string CMsgURLRequest::s_coMessageType = "URLRequest";
const uint CMsgURLRequest::s_nMessageTypeId = 0x0001;


void CMsgURLRequest::setContent(char* p_pchContent)
	throw (out_of_range, xml_parse_error, invalid_message_content)
{
	// read the message type identifier
	uint nMessageTypeId = strtol(p_pchContent, NULL, 16);
	if (nMessageTypeId != s_nMessageTypeId)
		throw invalid_message_content(string("invalid message type identifier ") + int2string(nMessageTypeId) + "; expected " + int2string(s_nMessageTypeId));

	// read the message data size
	uint nDataSize = strtol(p_pchContent + 5, NULL, 16);

	// set the pointer to message data
	const char* pchData = p_pchContent + 10;

	// initialize the xml reader
	CXMLReader coReader(pchData, nDataSize, "", NULL, 0);

	// read the first node
	coReader.nextElement(CampsiteMessage);

	// read MessageType attribute
	const char* pchValue = coReader.getAttributeValue(MessageType);
	// the received message type must be the same as my message type
	if (strcmp(pchValue, s_coMessageType.c_str()) != 0)
		throw invalid_message_content(string("invalid message type ") + pchValue + "; " + s_coMessageType + " was expected");

	// read MessageId attribute
	pchValue = coReader.getAttributeValue(MessageId);
	setId(strtol(pchValue, NULL, 10));

	// read the URL request attributes values
	m_coHTTPHost = coReader.nextElementContent("HTTPHost", 1);
	m_coDocumentRoot = coReader.nextElementContent("DocumentRoot", 1);
	m_coRemoteAddress = coReader.nextElementContent("RemoteAddress", 1);
	m_coPathTranslated = coReader.nextElementContent("PathTranslated", 1);
	m_coPathInfo = coReader.nextElementContent("PathInfo", 1);
	m_coRequestMethod = coReader.nextElementContent("RequestMethod", 1);
	m_coRequestURI = coReader.nextElementContent("RequestURI", 1);

	// read the request parameters
	coReader.nextElement("Parameters");
	const char* pchElement = coReader.nextElement();
	while (coReader.elementDepth() == 2)
	{
		if (strcasecmp(pchElement, "Parameter") != 0)
			continue;
		string coName = coReader.getAttributeValue("Name");
		string coAttr = coReader.getAttributeValue("Type");
		long nSize;
		if (strcasecmp(coAttr.c_str(), "string") != 0)
			nSize = strtol(coReader.getAttributeValue("Size"), NULL, 10);

		coReader.nextElement("#text");
		const char* pchContent = coReader.elementContent();
		if (strcasecmp(coAttr.c_str(), "string") == 0)
			setParameter(coName, string(pchContent));
		else
			setParameter(coName, pair<long, const char*> (nSize, pchContent));

		pchElement = coReader.nextElement();
		pchElement = coReader.nextElement();
	}

	pchElement = coReader.nextElement("Cookies");
	pchElement = coReader.nextElement();
	while (coReader.elementDepth() == 2)
	{
		if (strcasecmp(pchElement, "Cookie") != 0)
			continue;
		string coName = coReader.getAttributeValue("Name");
		coReader.nextElement("#text");
		m_coCookies[coName] = string(coReader.elementContent());
		pchElement = coReader.nextElement();
		pchElement = coReader.nextElement();
	}

	// set the pointer to the content buffer
	m_pchContent = p_pchContent;
	m_nContentSize = nDataSize + 10;
	m_bValidContent = true;
}

pair<long, const char*> CMsgURLRequest::getContent() const
{
	return pair<long, const char*> (m_nContentSize, m_pchContent);
}


// CMsgURLServe static members initialisation
const string CMsgURLServe::s_coMessageType = "URLServe";
const uint CMsgURLServe::s_nMessageTypeId = 0x1001;


// CMsgURLServe inline methods

void CMsgURLServe::setContent(char* p_pchContent)
	throw (out_of_range, xml_parse_error, invalid_message_content)
{
}

pair<long, const char*> CMsgURLServe::getContent() const
{
}
