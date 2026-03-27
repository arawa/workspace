import { getClient, defaultRootPath, resultToNode } from '@nextcloud/files/dav'

/**
 * Get the content of the README.md file for a workspace
 * @param {string} foldername is the Team Folder name.
 */
export function getReadme(foldername) {
	const client = getClient()

	const response = client.stat(`${defaultRootPath}/${foldername}`, {
		details: true,
		data: `<?xml version="1.0"?>
	<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns" xmlns:oc="http://owncloud.org/ns" xmlns:ocs="http://open-collaboration-services.org/ns">
		<d:prop>
			<d:getcontentlength /> <d:getcontenttype /> <d:getetag /> <d:getlastmodified /> <d:creationdate /> <d:displayname /> <d:quota-available-bytes /> <d:resourcetype /> <nc:has-preview /> <nc:is-encrypted /> <nc:mount-type /> <oc:comments-unread /> <oc:favorite /> <oc:fileid /> <oc:owner-display-name /> <oc:owner-id /> <oc:permissions /> <oc:size /> <nc:hidden /> <nc:is-mount-root /> <nc:metadata-blurhash /> <nc:metadata-files-live-photo /> <nc:note /> <nc:sharees /> <nc:hide-download /> <nc:share-attributes /> <oc:share-types /> <ocs:share-permissions /> <nc:system-tags /> <nc:rich-workspace /> <nc:rich-workspace-file />
		</d:prop>
	</d:propfind>
	`,
	})
		.then((result) => {
			const node = resultToNode(result.data)
			return node._data.attributes['rich-workspace']
		})

	return response
}
